<?php
	UOJContest::init(UOJRequest::get('id')) || UOJResponse::page404();
	isSuperUser(Auth::user()) || UOJResponse::page403();
    
	$contest = UOJContest::info();
	
	$time_form = new UOJForm('time');
	$time_form->addInput(
		'name', 'text', '比赛标题', $contest['name'],
		function($str) {
			return '';
		},
		null
	);
	$time_form->addInput(
		'start_time', 'text', '开始时间', $contest['start_time_str'],
		function($str, &$vdata) {
            $vdata['start_time'] = UOJTime::str2time($str);
            if ($vdata['start_time'] === null) {
				return '无效时间格式';
			}
			return '';
		},
		null
	);
	$time_form->addInput(
		'last_min', 'text', '时长（单位：分钟）', $contest['last_min'],
		function($str) {
			return !validateUInt($str) ? '必须为一个整数' : '';
		},
		null
	);
	$time_form->handle = function(&$vdata) {
		global $contest;
		$start_time_str = $vdata['start_time']->format('Y-m-d H:i:s');
		
		$purifier = HTML::purifier();
		
		$esc_name = $_POST['name'];
		$esc_name = $purifier->purify($esc_name);
		
		DB::update([
            "update contests",
            "set", [
                "start_time" => $start_time_str,
                "last_min" => $_POST['last_min'],
                "name" => $esc_name
            ], "where", ["id" => $contest['id']]
        ]);
	};
	
	$managers_form = new UOJAddDelCmdForm(
		'managers',
		'validateUserAndStoreByUsername',
		function($type, $username, &$vdata) {
			global $contest;
			$user = $vdata['user'][$username];
			if ($type == '+') {
				DB::insert([
                    "insert into contests_permissions",
                    "(contest_id, username)",
                    "values", DB::tuple([$contest['id'], $user['username']])
                ]);
			} else if ($type == '-') {
				DB::delete([
                    "delete from contests_permissions",
                    "where", [
                        "contest_id" => $contest['id'],
                        "username" => $user['username']
                    ]
                ]);
			}
		}
	);

	$contest_batch_registration_form = new UOJAddDelCmdForm(
		'contestbatchregistration',
		'validateUserAndStoreByUsername',
		function($type, $username, &$vdata) {
			global $contest;
			$user = $vdata['user'][$username];
			if ($type == '+') {
				DB::insert([
					"insert into contests_registrants",
					"(username, user_rating, contest_id, has_participated)",
					"values", DB::tuple([$user['username'], $user['rating'], $contest['id'], 0])
				]);
			} else if ($type == '-') {
				DB::delete([
                    "delete from contests_registrants",
                    "where", [
                        "contest_id" => $contest['id'],
                        "username" => $user['username']
                    ]
                ]);
			}
		},
		function() {
			global $contest;
			updateContestPlayerNum($contest);
		}
	);
	
	$problems_form = new UOJAddDelCmdForm(
		'problems',
		function($cmd, &$vdata) {
			if (!preg_match('/^(\d+)\s*(\[\S+\])?$/', $cmd, $matches)) {
				return "无效题号";
			}
			$problem_id = $matches[1];
			$problem = UOJProblem::query($problem_id);
			if (!$problem) {
				return "不存在题号为{$problem_id}的题";
			}
			if (!$problem->userCanManage(Auth::user())) {
				return "无权添加题号为{$problem_id}的题";
			}
			return '';
		},
		function($type, $cmd, &$vdata) {
			global $contest;
			
			if (!preg_match('/^(\d+)\s*(\[\S+\])?$/', $cmd, $matches)) {
				return "无效题号";
			}
			
			$problem_id = $matches[1];
			
			if ($type == '+') {
				DB::insert([
                    "insert ignore into contests_problems",
                    "(contest_id, problem_id)",
                    "values", DB::tuple([$contest['id'], $problem_id])
                ]);

                if (isset($matches[2])) {
					$arg = $matches[2];
					if ($arg === '[bonus]') {
						$contest['extra_config']['bonus']["problem_$problem_id"] = true;
					} else {
						$judge_type = null;
						foreach (['sample', 'sample-no-details', 'full', 'no-details', 'default'] as $jt) {
							if ($arg === "[{$jt}]") {
								$judge_type = $jt;
							}
						}
						if ($judge_type === 'default') {
							unset($contest['extra_config']["problem_$problem_id"]);
						} else {
							$contest['extra_config']["problem_$problem_id"] = $judge_type;
						}
					}
					// TODO: provide a way to set submit_time_limit?
				}
			} else if ($type == '-') {
				DB::delete([
                    "delete from contests_problems",
                    "where", [
                        "contest_id" => $contest['id'],
                        "problem_id" => $problem_id
                    ]
                ]);
                
                unset($contest['extra_config']["problem_$problem_id"]);
                unset($contest['extra_config']['bonus']["problem_$problem_id"]);
                unset($contest['extra_config']['submit_time_limit']["problem_$problem_id"]);
			}
		},
		function() {
			global $contest;
			$esc_extra_config = json_encode($contest['extra_config']);
			DB::update([
				"update contests",
				"set", ["extra_config" => $esc_extra_config],
				"where", ["id" => $contest['id']]
			]);
		}
	);
	
    $rule_form = new UOJForm('rule');
    $rule_form->addSelect('basic_rule', [
        'UOJ-OI' => 'UOJ-OI 赛制',
        'UOJ-ACM' => 'UOJ-ACM 赛制',
        'UOJ-IOI' => 'UOJ-IOI 赛制'
    ], "基本赛制", $contest['extra_config']['basic_rule']);
    $rule_form->addSelect('free_registration', [
        1 => '所有人都可以自由报名',
        0 => '只能由管理员帮选手报名'
    ], "报名方式", $contest['extra_config']['free_registration']);
    $rule_form->addSelect('individual_or_team', [
        'individual' => '个人赛',
        'team' => '团体赛'
    ], "个人赛/团体赛", $contest['extra_config']['individual_or_team']);
    $rule_form->addSelect('forzen_time_mode', [
        'no_freeze' => '不封榜',
        'freeze_last_1_over_5' => '比赛结束前 1/5 的时间封榜（若比赛为5小时，则最后一小时封榜）',
        'all_freeze' => '全程封榜（从一开始就看不到别人的得分）'
    ], "什么时候封榜？", $contest['extra_config']['forzen_time_mode']);
    $rule_form->addInput(
        'max_n_submissions_per_problem', 'text', '每题最高提交次数（-1 表示不限制）',
        $contest['extra_config']['max_n_submissions_per_problem'],
        function($str) {
            return !validateUInt($str) && $str !== '-1' ? '必须为一个非负整数或-1' : '';
        },
        null
    );
    $rule_form->addSelect('sample_test_name', [
        'sample_test' => '样例测评',
        'pretest' => '预测评',
    ], "如何称呼sample型的题目在比赛期间的测评方式？", $contest['extra_config']['sample_test_name']);
    $rule_form->handle = function() {
        global $contest;
        $contest['extra_config']['basic_rule'] = $_POST['basic_rule'];
        $contest['extra_config']['free_registration'] = (int)$_POST['free_registration'];
        $contest['extra_config']['individual_or_team'] = $_POST['individual_or_team'];
        $contest['extra_config']['forzen_time_mode'] = $_POST['forzen_time_mode'];
        $contest['extra_config']['max_n_submissions_per_problem'] = (int)$_POST['max_n_submissions_per_problem'];
        $contest['extra_config']['sample_test_name'] = $_POST['sample_test_name'];
        $esc_extra_config = json_encode($contest['extra_config']);
        DB::update([
            "update contests",
            "set", ["extra_config" => $esc_extra_config],
            "where", ["id" => $contest['id']]
        ]);
    };
    $rule_form->submit_button_config['text'] = '更改赛制设置';
    $rule_form->submit_button_config['smart_confirm'] = '';
    $rule_form->runAtServer();
    
    $rating_k_form = new UOJForm('rating_k');
    $rating_k_form->addInput('rating_k', 'text', 'rating 变化上限', isset($contest['extra_config']['rating_k']) ? $contest['extra_config']['rating_k'] : 400,
        function ($x) {
            if (!validateUInt($x) || $x < 1 || $x > 1000) {
                return '不合法的上限';
            }
            return '';
        },
        null
    );
    $rating_k_form->handle = function() {
        global $contest;
        $contest['extra_config']['rating_k'] = $_POST['rating_k'];
        $esc_extra_config = json_encode($contest['extra_config']);
        DB::update([
            "update contests",
            "set", ["extra_config" => $esc_extra_config],
            "where", ["id" => $contest['id']]
        ]);
    };
    $rating_k_form->runAtServer();
    
    $rated_form = new UOJForm('rated');
    $rated_form->handle = function() {
        global $contest;
        if (isset($contest['extra_config']['unrated'])) {
            unset($contest['extra_config']['unrated']);
        } else {
            $contest['extra_config']['unrated'] = '';
        }
        $esc_extra_config = json_encode($contest['extra_config']);
        DB::update([
            "update contests",
            "set", ["extra_config" => $esc_extra_config],
            "where", ["id" => $contest['id']]
        ]);
    };
    $rated_form->submit_button_config['class_str'] = 'btn btn-warning btn-block';
    $rated_form->submit_button_config['text'] = isset($contest['extra_config']['unrated']) ? '设置比赛为rated' : '设置比赛为unrated';
    $rated_form->submit_button_config['smart_confirm'] = '';

    $rated_form->runAtServer();
    
    $version_form = new UOJForm('version');
    $version_form->addInput('standings_version', 'text', '排名版本', $contest['extra_config']['standings_version'],
        function ($x) {
            if (!validateUInt($x) || $x < 1 || $x > 2) {
                return '不是合法的版本号';
            }
            return '';
        },
        null
    );
    $version_form->handle = function() {
        global $contest;
        $contest['extra_config']['standings_version'] = $_POST['standings_version'];
        $esc_extra_config = json_encode($contest['extra_config']);
        DB::update([
            "update contests",
            "set", ["extra_config" => $esc_extra_config],
            "where", ["id" => $contest['id']]
        ]);
    };
    $version_form->runAtServer();
	
	$time_form->runAtServer();
	$managers_form->runAtServer();
	$contest_batch_registration_form->runAtServer();
	$problems_form->runAtServer();
?>
<?php echoUOJPageHeader(HTML::stripTags($contest['name']) . ' - 比赛管理') ?>
<div class="text-center">
    <h1 class="page-header"><?=$contest['name']?> 管理</h1>
</div>
<ul class="nav nav-tabs" role="tablist">
	<li class="active"><a href="#tab-time" role="tab" data-toggle="tab">比赛时间</a></li>
	<li><a href="#tab-managers" role="tab" data-toggle="tab">管理者</a></li>
	<li><a href="#tab-batch-registration" role="tab" data-toggle="tab">批量报名</a></li>
	<li><a href="#tab-problems" role="tab" data-toggle="tab">试题</a></li>
	<li><a href="#tab-others" role="tab" data-toggle="tab">其它</a></li>
	<li><a href="/contest/<?=$contest['id']?>" role="tab">返回</a></li>
</ul>
<div class="tab-content top-buffer-sm">
	<div class="tab-pane active" id="tab-time">
		<?php $time_form->printHTML(); ?>
	</div>
	
	<div class="tab-pane" id="tab-managers">
		<table class="table table-hover">
			<thead>
				<tr>
					<th>#</th>
					<th>用户名</th>
				</tr>
			</thead>
			<tbody>
<?php
	$row_id = 0;
	$res = DB::selectAll([
        "select username from contests_permissions",
        "where", ["contest_id" => $contest['id']]
    ]);
	foreach ($res as $row) {
		$row_id++;
		echo '<tr>', '<td>', $row_id, '</td>', '<td>', getUserLink($row['username']), '</td>', '</tr>';
	}
?>
			</tbody>
		</table>
		<p class="text-center">命令格式：命令一行一个，+mike表示把mike加入管理者，-mike表示把mike从管理者中移除</p>
		<?php $managers_form->printHTML(); ?>
	</div>
	
	<div class="tab-pane" id="tab-batch-registration">
		<table class="table table-hover">
			<thead>
				<tr>
					<th>#</th>
					<th>用户名</th>
				</tr>
			</thead>
			<tbody>
<?php
	$row_id = 0;
	$res = DB::selectAll([
        "select username from contests_registrants",
        "where", ["contest_id" => $contest['id']]
    ]);
	foreach ($res as $row) {
		$row_id++;
		echo '<tr>', '<td>', $row_id, '</td>', '<td>', getUserLink($row['username']), '</td>', '</tr>';
	}
?>
			</tbody>
		</table>
		<p class="text-center">命令格式：命令一行一个，+mike表示帮mike报名比赛，-mike表示把mike从报名列表中移除</p>
		<?php $contest_batch_registration_form->printHTML(); ?>
	</div>
	
	<div class="tab-pane" id="tab-problems">
		<table class="table table-hover">
			<thead>
				<tr>
					<th>#</th>
					<th>试题名</th>
				</tr>
			</thead>
			<tbody>
<?php
	$res = DB::selectAll([
        "select problem_id from contests_problems",
        "where", ["contest_id" => $contest['id']],
        "order by problem_id asc"
    ]);
	foreach ($res as $num => $row) {
        $problem = UOJContestProblem::query($row['problem_id']);
        $problem->problem_number = $num;
        $problem_config_str = $problem->getJudgeTypeInContest();
        if ($problem->isBonus()) {
			$problem_config_str .= ', bonus';
        }
		echo '<tr>', '<td>', $problem->getLetter(), '</td>', '<td>', $problem->getLink(), ' ', "[$problem_config_str]", '</td>', '</tr>';
	}
?>
			</tbody>
		</table>
		<ul>
			<li><strong>命令基本格式：</strong>命令一行一个，+233表示把题号为233的试题加入比赛，-233表示把题号为233的试题从比赛中移除。</li>
			<li><strong>设置测评类型：</strong> UOJ 的比赛题目有如下四种测评类型。可以通过 +233[no-details] 这样的语法来手动设置测评类型。不加括号直接通过 +233 添加题目时，测评类型为 default。
				<ul>
					<li>sample：根据题目配置进行简单样例测评或预测评，显示每个测试点分数和详情；</li>
					<li>sample-no-details：根据题目配置进行简单样例测评或预测评，显示每个测试点分数，不显示详情；</li>
					<li>full：测全部数据，显示每个测试点分数和详情；</li>
					<li>no-details：测全部数据，显示每个测试点分数，不显示详情；</li>
					<li>default：设为本场比赛的赛制对应的默认测评类型。在 OI 赛制里为 sample，其余赛制里为 no-details。</li>
				</ul>
			</li>
			<li><strong>设置是否为 bonus 题：</strong> 在 ACM 赛制中，如果设置一道题目为 bonus，那么获得 100 分后会总罚时会减少 20 分钟，但排名时不会将此题记入该选手通过的题目总数中。用 +233[bonus] 来将一道题设为 bonus。</li>
		</ul>
		<?php $problems_form->printHTML(); ?>
	</div>
	<div class="tab-pane" id="tab-others">
		<div class="row">
			<div class="col-sm-12">
				<h3>赛制解释</h3>
				<ul>
				    <li><strong>UOJ-OI 赛制：</strong> 比赛期间可设置题目只进行简单样例测评或预测评，结束后会进行重测。按最后一次有效提交算分和算罚时。</li>
				    <li><strong>UOJ-ACM 赛制：</strong> 比赛期间所有题目显示最终测评结果（须手工将题目类型设置为 no-details 或 full）。比赛结束前一小时封榜，比赛时间不足5小时则比赛过去五分之四后封榜。一道题的罚时为得分最高的提交的时间，加上在此之前没有使得该题分数增加的提交的次数乘以 20 分钟。</li>
				    <li><strong>UOJ-IOI 赛制：</strong> 比赛期间所有题目显示最终测评结果（须手工将题目类型设置为 no-details 或 full）。按得分最高的有效提交算分和算罚时。</li>
				</ul>
				<h3>赛制设置</h3>
				<?php $rule_form->printHTML(); ?>
				<?php if (!isset($contest['extra_config']['unrated'])): ?>
				<h3>Rating控制：当前比赛为 Rated</h3>
				<div class="row">
					<div class="col-sm-3">
						<?php $rated_form->printHTML(); ?>
					</div>
				</div>
				<div class="top-buffer-sm"></div>
				<?php $rating_k_form->printHTML(); ?>
				<?php else: ?>
				<h3>Rating控制：当前比赛为 Unrated</h3>
				<div class="row">
					<div class="col-sm-3">
						<?php $rated_form->printHTML(); ?>
					</div>
				</div>
				<?php endif ?>
				
			</div>
			<div class="col-sm-12 top-buffer-sm">
				<h3>版本控制</h3>
				<?php $version_form->printHTML(); ?>
			</div>
		</div>
	</div>
</div>
<?php echoUOJPageFooter() ?>
