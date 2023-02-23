<?php

requirePHPLib('form');
requirePHPLib('judger');

if ($myUser == null || !isSuperUser($myUser)) {
	UOJResponse::page403();
}

$user_form = new UOJForm('user');
$user_form->addInput('username', 'text', '用户名', '',
	function ($username) {
		if (!validateUsername($username)) {
			return '用户名不合法';
		}
		if (!UOJUser::query($username)) {
			return '用户不存在';
		}
		return '';
	},
	null
);
$options = [
	'ban' => '封禁',
	'deblocking' => '解封',
	'login' => '登录',
	'delete-submissions' => '删除提交记录',
	'delete-blogs' => '删除所有博客'
];
$user_form->addSelect('op_type', $options, '操作类型', '');
$user_form->handle = function() {
	global $user_form;
	
	$username = $_POST['username'];
	switch ($_POST['op_type']) {
		case 'ban':
			DB::update([
				"update user_info",
				"set", ["usergroup" => 'B'],
				"where", ["username" => $username]
			]);
			break;
		case 'deblocking':
			DB::update([
				"update user_info",
				"set", ["usergroup" => 'U'],
				"where", ["username" => $username]
			]);
			break;
		case 'login':
			Auth::login($username);
			$user_form->succ_href = "/";
			break;
		case 'delete-submissions':
			$submissions = DB::selectAll([
				"select * from submissions",
				"where", ["submitter" => $username]
			]);
			$problem_ids = [];
			foreach ($submissions as $submission) {
				$problem_ids[] = $submission['problem_id'];
				
				$content = json_decode($submission['content'], true);
				unlink(UOJContext::storagePath().$content['file_name']);
				DB::delete([
					"delete from submissions",
					"where", ["id" => $submission['id']]
				]);
			}
			foreach ($problem_ids as $problem_id) {
				updateBestACSubmissions($username, $problem_id);
			}
			break;
		case 'delete-blogs':
			$blogs = DB::selectAll([
				"select id from blogs",
				"where", ["poster" => $username]
			]);
			foreach ($blogs as $blog) {
				UOJBlog::deleteByID($blog['id']);
			}
			break;
	}
};
$user_form->submit_button_config['smart_confirm'] = '';
$user_form->runAtServer();

$register_tmp_acm_team_form = new UOJForm('registertmpacmteam');
$register_tmp_acm_team_form->addTextArea('table', '队伍信息', '', 'validateNothing', null);
$register_tmp_acm_team_form->addInput('contest_name', 'text', 'ACM比赛名称', '某ACM比赛', 'validateNothing', null);
$register_tmp_acm_team_form->addInput(
	'expiration_time', 'text', '过期时间', date("Y-m-d H:i:s"),
	function($str, &$vdata) {
		try {
			$vdata['expiration_time'] = new DateTime($str);
		} catch (Exception $e) {
			return '无效时间格式';
		}
		return '';
	},
	null
);
$register_tmp_acm_team_form->handle = function(&$vdata) {
	$msg = '<pre>';
	
	$contest_name = UOJRequest::post('contest_name');
	$expiration_time = $vdata['expiration_time']->format('Y-m-d H:i:s');
	foreach (explode("\n", UOJRequest::post('table')) as $raw_line) {
		try {
			if (trim($raw_line) === '') {
				continue;
			}
			$team = UOJUser::registerTmpACMTeamAccountFromText($raw_line, $contest_name, $expiration_time);
			$msg .= $team['username'].' : success';
		} catch (Exception $e) {
			$msg .= $raw_line.' : '.$e->getMessage(); 
		}
		$msg .= "\n";
	}
	$msg .= '</pre>';
	UOJResponse::message($msg);
};
$register_tmp_acm_team_form->runAtServer();

$blog_link_contests = new UOJForm('blog_link_contests');
$blog_link_contests->addInput('blog_id', 'text', '博客ID', '', 'validateBlogId', null);
$blog_link_contests->addInput('contest_id', 'text', '比赛ID', '', 'validateContestId', null);
$blog_link_contests->addInput('title', 'text', '标题', '', 'validateNothing', null);
$options = [
	'add' => '添加',
	'del' => '删除'
];
$blog_link_contests->addSelect('op_type', $options, '操作类型', '');
$blog_link_contests->handle = function() {
	$blog_id = $_POST['blog_id'];
	$contest_id = $_POST['contest_id'];
	$str = DB::selectFirst([
		"select * from contests",
		"where", ["id" => $contest_id]
	]);
	$all_config = json_decode($str['extra_config'], true);
	$config = isset($all_config['links']) ? $all_config['links'] : [];

	$n = count($config);
	
	if ($_POST['op_type'] == 'add') {
		$row = [];
		$row[0] = $_POST['title'];
		$row[1] = $blog_id;
		$config[$n] = $row;
	}
	if ($_POST['op_type'] == 'del') {
		for ($i = 0; $i < $n; $i++)
			if ($config[$i][1] == $blog_id) {
				$config[$i] = $config[$n - 1];
				unset($config[$n - 1]);
				break;
			}
	}

	$all_config['links'] = $config;
	$str = json_encode($all_config);
	DB::update([
		"update contests",
		"set", ["extra_config" => $str],
		"where", ["id" => $contest_id]
	]);
};
$blog_link_contests->runAtServer();

$blog_link_index = new UOJForm('blog_link_index');
$blog_link_index->addInput('blog_id2', 'text', '博客ID', '', 'validateBlogId', null);
$blog_link_index->addInput('blog_level', 'text', '置顶级别（删除不用填）', '0',
	function ($x) {
		if (!validateUInt($x)) return '数字不合法';
		if ($x > 3) return '该级别不存在';
		return '';
	},
	null
);
$options = [
	'add' => '添加',
	'del' => '删除'
];
$blog_link_index->addSelect('op_type2', $options, '操作类型', '');
$blog_link_index->handle = function() {
	$blog_id = $_POST['blog_id2'];
	$blog_level = $_POST['blog_level'];
	if ($_POST['op_type2'] == 'add') {
		$res = DB::selectFirst([
			"select * from important_blogs",
			"where", ["blog_id" => $blog_id]
		]);
		if ($res) {
			DB::update([
				"update important_blogs",
				"set", ["level" => $blog_level],
				"where", ["blog_id" => $blog_id]
			]);
		} else {
			DB::insert([
				"insert into important_blogs",
				"(blog_id, level)",
				"values", DB::tuple([$blog_id, $blog_level])
			]);
		}
	}
	if ($_POST['op_type2'] == 'del') {
		DB::delete([
			"delete from important_blogs",
			"where", ["blog_id" => $blog_id]
		]);
	}
};
$blog_link_index->runAtServer();

$blog_deleter = new UOJForm('blog_deleter');
$blog_deleter->addInput('blog_del_id', 'text', '博客ID', '', 'validateBlogId', null);
$blog_deleter->handle = function(&$vdata) {
	UOJBlog::deleteByID($_POST['blog_del_id']);
};
$blog_deleter->runAtServer();

$blog_deleter2 = new UOJForm('blog_interval_deleter');
$blog_deleter2->addInput('blog_del_L', 'text', '起始博客ID', '', 'validateBlogId', null);
$blog_deleter2->addInput('blog_del_R', 'text', '结束博客ID', '', 'validateBlogId', null);
$blog_deleter2->handle = function() {
	for ($id = $_POST['blog_del_L']; $id <= $_POST['blog_del_R']; $id++) {
		UOJLog::error("super: delete blog $id");
		UOJBlog::deleteByID($id);
	}
};
$blog_deleter2->runAtServer();

$comment_hider = new UOJForm('comment_hider');
$comment_hider->addInput('comment_hide_id', 'text', '评论ID（可从HTML源码中找到）', '', 'validateCommentId', null);
$comment_hider->addTextArea(
	'comment_hide_reason', '理由（理由为空表示解除隐藏）',
	'该评论（选择：疑似为无意义的乱码/涉嫌辱骂他人/涉嫌发表致页面卡顿的内容/涉嫌黄赌毒/涉嫌违法内容），已被管理员隐藏',
	'validateString', null
);
$comment_hider->handle = function(&$vdata) {
	$vdata['comment_hide_id']->hide($_POST['comment_hide_reason']);
};
$comment_hider->runAtServer();

$contest_submissions_deleter = new UOJForm('contest_submissions');
$contest_submissions_deleter->addInput('contest_id', 'text', '比赛ID', '', 'validateContestId', null);
$contest_submissions_deleter->handle = function() {
	$contest = UOJContest::query($_POST['contest_id']);
	
	$contest_problems = DB::selectAll([
		"select problem_id from contests_problems",
		"where", ["contest_id" => $contest->info['id']]
	]);
	foreach ($contest_problems as $problem) {
		$submissions = DB::selectAll([
			"select * from submissions",
			"where", [
				"problem_id" => $problem['problem_id'],
				["submit_time", "<", $contest->info['start_time_str']]
			]
		]);
		foreach ($submissions as $submission) {
			$content = json_decode($submission['content'], true);
			unlink(UOJContext::storagePath().$content['file_name']);
			DB::delete([
				"delete from submissions",
				"where", ["id" => $submission['id']]
			]);
			updateBestACSubmissions($submission['submitter'], $submission['problem_id']);
		}
	}
};
$contest_submissions_deleter->runAtServer();

$custom_test_deleter = new UOJForm('custom_test_deleter');
$custom_test_deleter->addInput('last', 'text', '删除末尾记录', '5',
	function ($x, &$vdata) {
		if (!validateUInt($x)) {
			return '不合法';
		}
		$vdata['last'] = $x;
		return '';
	},
	null
);
$custom_test_deleter->handle = function(&$vdata) {
	$all = DB::selectAll([
		"select * from custom_test_submissions",
		"order by id asc",
		DB::limit($vdata['last'])
	]);
	foreach ($all as $submission) {
		$content = json_decode($submission['content'], true);
		unlink(UOJContext::storagePath().$content['file_name']);
	}
	DB::delete([
		"delete from custom_test_submissions",
		"order by id asc",
		DB::limit($vdata['last'])
	]);
};
$custom_test_deleter->runAtServer();

$banlist_cols = ['username', 'usergroup'];
$banlist_config = [];
$banlist_header_row = <<<EOD
<tr>
	<th>用户名</th>
</tr>
EOD;
$banlist_print_row = function($row) {
	$hislink = getUserLink($row['username']);
	echo <<<EOD
	<tr>
		<td>${hislink}</td>
	</tr>
	EOD;
};


$active_user_rule_form = new UOJForm('active_user_rule');
$active_user_rule_form->addInput('M', 'number', 'M', UOJContext::getMeta('active_duration_M'),
	function ($x, &$vdata) {
		if (!validateUInt($x)) {
			return '不合法';
		}
		$vdata['M'] = (int)$x;
		return '';
	},
	null
);
$active_user_rule_form->handle = function(&$vdata) {
	UOJContext::setMeta('active_duration_M', $vdata['M']);
	UOJRanklist::updateActiveUserList();
};
$active_user_rule_form->runAtServer();

$submission_frequency = UOJContext::getMeta('submission_frequency');
$submission_frequency_form = new UOJForm('submission_frequency');
$submission_frequency_form->addSelect('submission_frequency_interval', [
	'PT1S' => '1 秒',
	'PT10S' => '10 秒',
	'PT1M' => '1 分钟',
	'PT10M' => '10 分钟',
	'PT30M' => '30 分钟',
	'PT1H' => '1 小时',
], '时间间隔', $submission_frequency['interval']);
$submission_frequency_form->addInput('submission_frequency_limit', 'number', '最大提交次数', $submission_frequency['limit'], function ($x, &$vdata) {
	if (!validateUInt($x)) {
		return '不合法';
	}
	$vdata['limit'] = (int)$x;
	return '';
}, null);
$submission_frequency_form->handle = function (&$vdata) {
	UOJContext::setMeta('submission_frequency', [
		'interval' => UOJRequest::post('submission_frequency_interval'),
		'limit' => $vdata['limit'],
	]);
};
$submission_frequency_form->runAtServer();

$cur_tab = isset($_GET['tab']) ? $_GET['tab'] : 'users';

$tabs_info = [
	'users' => [
		'name' => '用户操作',
		'url' => "/super-manage/users"
	],
	'tmp-users' => [
		'name' => '临时用户管理',
		'url' => '/super-manage/tmp-users'
	],
	'blogs' => [
		'name' => '博客管理',
		'url' => "/super-manage/blogs"
	],
	'submissions' => [
		'name' => '提交记录',
		'url' => "/super-manage/submissions"
	],
	'custom-test' => [
		'name' => '自定义测试',
		'url' => '/super-manage/custom-test'
	],
	'non-trad-problems' => [
		'name' => '非传统题',
		'url' => "/super-manage/non-trad-problems"
	],
	'click-zan' => [
		'name' => '点赞管理',
		'url' => '/super-manage/click-zan'
	],
	'search' => [
		'name' => '搜索管理',
		'url' => '/super-manage/search'
	],
	'meta' => [
		'name' => 'OJ基础设置',
		'url' => '/super-manage/meta'
	]
];

isset($tabs_info[$cur_tab]) || UOJResponse::page404();

requireLib('shjs');
requireLib('morris');

?>
<?php echoUOJPageHeader('系统管理') ?>
<div class="row">
	<div class="col-sm-3">
		<?= HTML::tablist($tabs_info, $cur_tab, 'nav-pills nav-stacked') ?>
	</div>
	
	<div class="col-sm-9">
		<?php if ($cur_tab === 'users'): ?>
			<?php
				foreach ([1, 10, 60, 100] as $min) {
					$recent = clone UOJTime::$time_now;
					$recent->sub(new DateInterval("PT{$min}M"));
					echo "<h3>最近{$min}分钟内访问过OJ的用户数：";
					echo DB::selectCount([
						"select count(*) from user_info",
						"where", [["last_login_time", ">=", UOJTime::time2str($recent)]]
					]);
					echo '</h3>';
				}
			?>
			<h3>最近1分钟内访问OJ的用户：</h3>
			<table class="table table-hover">
				<thead>
					<tr>
						<th>#</th>
						<th>时间</th>
						<th>用户名</th>
					</tr>
				</thead>
				<tbody>
				<?php
					$recent = clone UOJTime::$time_now;
					$recent->sub(new DateInterval("PT1M"));
					$res = DB::selectAll([
						"select * from user_info",
						"where", [["last_login_time", ">=", UOJTime::time2str($recent)]],
						"order by last_login_time desc"
					]);
					foreach ($res as $key => $user) {
						echo '<tr>', '<td>', $key + 1, '</td>', '<td>', $user['last_login_time'], '</td>', '<td>', getUserLink($user['username'], $user['rating']), '</td>', '</tr>';
					}
				?>
				</tbody>
			</table>
			
			<?php $user_form->printHTML(); ?>
			<h3>封禁名单</h3>
			<?php echoLongTable($banlist_cols, 'user_info', ["usergroup" => 'B'], '', $banlist_header_row, $banlist_print_row, $banlist_config) ?>
		<?php elseif ($cur_tab === 'tmp-users'): ?>
			<h4>ACM比赛的队伍临时账号注册</h4>
			<p>一行一个队伍，格式形如 “miketeam,123456,mike@uoj.ac,Mike代表队,2,李麦克,麦麦大学,张麦克,克克大学”，依次表示用户名、密码、邮箱、队伍名、队伍人数、每个队员的姓名和单位信息。</p>
			<p>注意分隔符为英文逗号。解析时调用的是 PHP 的 str_getcsv 函数，遇到特殊字符请遵照 CSV 格式进行编码。</p>
			<?php $register_tmp_acm_team_form->printHTML(); ?>
		<?php elseif ($cur_tab === 'blogs'): ?>
			<div>
				<h4>添加到比赛链接</h4>
				<?php $blog_link_contests->printHTML(); ?>
			</div>

			<div>
				<h4>添加到公告</h4>
				<?php $blog_link_index->printHTML(); ?>
			</div>
		
			<div>
				<h4>删除博客</h4>
				<?php $blog_deleter->printHTML(); ?>
				
				<h4>批量删除博客（慎用！一定要看清ID！写错了的话没有挽救措施！）</h4>
				<?php $blog_deleter2->printHTML(); ?>
			</div>

			<div>
				<h4>隐藏评论</h4>
				<?php $comment_hider->printHTML(); ?>
			</div>
		<?php elseif ($cur_tab === 'submissions'): ?>
			<div>
				<h4>删除赛前提交记录</h4>
				<?php $contest_submissions_deleter->printHTML(); ?>
			</div>
			<div>
				<h4>测评失败的提交记录</h4>
				<?php
				echoSubmissionsList(
					DB::lor([
						['result_error', '=', 'Judgment Failed'],
						['result_error', '=', 'Judgement Failed'],
					]), 'order by id desc', [], Auth::user()
				);
				?>
			</div>
		<?php elseif ($cur_tab === 'custom-test'): ?>
		<?php $custom_test_deleter->printHTML() ?>
		<?php
			$submissions_pag = new Paginator([
				'col_names' => ['*'],
				'table_name' => 'custom_test_submissions',
				'cond' => '1',
				'tail' => 'order by id asc',
				'page_len' => 5
            ]);
			foreach ($submissions_pag->get() as $submission) {
                $usubm = new UOJCustomTestSubmission($submission);
                $usubm->setProblem();
				echo '<dl class="dl-horizontal">';
				echo '<dt>id</dt>';
				echo '<dd>', "#{$usubm->info['id']}", '</dd>';
				echo '<dt>problem_id</dt>';
				echo '<dd>', "#{$usubm->info['problem_id']}", '</dd>';
				echo '<dt>submit time</dt>';
				echo '<dd>', $usubm->info['submit_time'], '</dd>';
				echo '<dt>submitter</dt>';
				echo '<dd>', $usubm->info['submitter'], '</dd>';
				echo '<dt>judge_time</dt>';
				echo '<dd>', $usubm->info['judge_time'], '</dd>';
                echo '</dl>';
                $usubm->echoContent();
				echoCustomTestSubmissionDetails($usubm->getResult('details'), "submission-{$usubm->info['id']}-details");
			}
		?>
		<?= $submissions_pag->pagination() ?>

		<?php elseif ($cur_tab === 'non-trad-problems'): ?>
		<h2 class="text-center">非传统题列表</h2>
		<?php

		$non_trad_type_map = [];
		
		echoLongTable(['*'], 'problems', "1", 'order by id asc',
			'<tr><th>ID</th><th>标题</th><th>非传统类型</th><tr>',
			function($info) {
				global $non_trad_type_map;

				$problem = new UOJProblem($info);
				echo '<tr>';
				echo '<td>', "#{$problem->info['id']}", '</td>';
				echo '<td>', $problem->getLink(), '</td>';
				echo '<td>', $non_trad_type_map[$problem->info['id']], '</td>';
				echo '</tr>';
			}, [
				'echo_full' => true,
				'post_filter' => function(&$info) {
					global $non_trad_type_map;

					$problem = new UOJProblem($info);
					$type = $problem->getNonTraditionalJudgeType();
					$non_trad_type_map[$info['id']] = $type;
					return $type !== false && $type !== 'traditional';
				}
			]);

		?>

		<?php elseif ($cur_tab === 'click-zan'): ?>
		没写好QAQ

		<?php elseif ($cur_tab === 'search'): ?>
		<h2 class="text-center">一周搜索情况</h2>
		<div id="search-distribution-chart-week" style="height: 250px;"></div>
		<script type="text/javascript">
			new Morris.Line({
				element: 'search-distribution-chart-week',
				data: <?= json_encode(DB::selectAll([
                    "select DATE_FORMAT(created_at, '%Y-%m-%d %h:00'), count(*) from search_requests",
                    "where", ["created_at > now() - interval 1 week"],
                    "group by DATE_FORMAT(created_at, '%Y-%m-%d %h:00')"
                ])) ?>,
				xkey: "DATE_FORMAT(created_at, '%Y-%m-%d %h:00')",
				ykeys: ["count(*)"],
				labels: ['number'],
				resize: true
			});
		</script>
		
		<h2 class="text-center">一月搜索情况</h2>
		<div id="search-distribution-chart-month" style="height: 250px;"></div>
		<script type="text/javascript">
			new Morris.Line({
				element: 'search-distribution-chart-month',
				data: <?= json_encode(DB::selectAll([
                    "select DATE_FORMAT(created_at, '%Y-%m-%d'), count(*) from search_requests",
                    "where", ["created_at > now() - interval 1 month"],
                    "group by DATE_FORMAT(created_at, '%Y-%m-%d')"
                ])) ?>,
				xkey: "DATE_FORMAT(created_at, '%Y-%m-%d')",
				ykeys: ["count(*)"],
				labels: ['number'],
				resize: true
			});
		</script>
		
		<?php echoLongTable(['*'], 'search_requests', "1", 'order by id desc',
			'<tr><th>id</th><th>created_at</th><th>remote_addr</th><th>type</th><th>q</th><tr>',
			function($row) {
				echo '<tr>';
				echo '<td>', $row['id'], '</td>';
				echo '<td>', $row['created_at'], '</td>';
				echo '<td>', $row['remote_addr'], '</td>';
				echo '<td>', $row['type'], '</td>';
				echo '<td>', HTML::escape($row['q']), '</td>';
				echo '</tr>';
			}, ['page_len' => 1000])
		?>

		<?php elseif ($cur_tab === 'meta'): ?>
		<h4>活跃用户判定标准</h4>
		<p><?= UOJLocale::get('active rule', 'M') ?></p>
		<?php $active_user_rule_form->printHTML() ?>
		
		<h4>提交频次限制</h4>
		<?php $submission_frequency_form->printHTML() ?>
		<?php endif ?>
	</div>
</div>
<?php echoUOJPageFooter() ?>
