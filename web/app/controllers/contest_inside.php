<?php
	requirePHPLib('judger');

    UOJContest::init(UOJRequest::get('id')) || UOJResponse::page404();
    UOJContest::cur()->userCanView(Auth::user(), ['ensure' => true]);

	$contest = UOJContest::info();
	$is_manager = UOJContest::cur()->userCanManage(Auth::user());

	if (isset($_GET['tab'])) {
		$cur_tab = $_GET['tab'];
	} else {
		$cur_tab = 'dashboard';
	}
	
	$tabs_info = array(
		'dashboard' => array(
			'name' => UOJLocale::get('contests::contest dashboard'),
			'url' => "/contest/{$contest['id']}"
		),
		'submissions' => array(
			'name' => UOJLocale::get('contests::contest submissions'),
			'url' => "/contest/{$contest['id']}/submissions"
		),
		'standings' => array(
			'name' => UOJLocale::get('contests::contest standings'),
			'url' => "/contest/{$contest['id']}/standings"
		)
	);
	
	if ($is_manager) {
		$tabs_info['backstage'] = [
			'name' => UOJLocale::get('contests::contest backstage'),
			'url' => "/contest/{$contest['id']}/backstage"
        ];
        if (UOJContest::cur()->managerCanSeeStandingsUnfrozenTab(Auth::user())) {
			$tabs_info['standings-unfrozen'] = [
				'name' => '滚榜',
				'url' => "/contest/{$contest['id']}/standings-unfrozen"
			];
        }
	}
	
    isset($tabs_info[$cur_tab]) || UOJResponse::page404();
	
	if (isSuperUser($myUser)) {
		if (CONTEST_PENDING_FINAL_TEST <= $contest['cur_progress'] && $contest['cur_progress'] <= CONTEST_TESTING) {
			$start_test_form = new UOJForm('start_test');
			$start_test_form->handle = function() {
				UOJContest::finalTest();
			};
			$start_test_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
			$start_test_form->submit_button_config['smart_confirm'] = '';
			$start_test_form->submit_button_config['text'] = UOJContest::cur()->labelForFinalTest();
			$start_test_form->runAtServer();
		}
		if ($contest['cur_progress'] >= CONTEST_TESTING) {
			$publish_result_form = new UOJForm('publish_result');
			$publish_result_form->handle = function() {
				UOJContest::announceOfficialResults();
			};
			$publish_result_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
			$publish_result_form->submit_button_config['smart_confirm'] = '';
			$publish_result_form->submit_button_config['text'] = ($contest['cur_progress'] == CONTEST_FINISHED ? '重新' : '').'宣布正式成绩';
			$publish_result_form->runAtServer();
		}
	}
	
	if ($cur_tab == 'dashboard') {
		if ($contest['cur_progress'] <= CONTEST_IN_PROGRESS) {
			$post_question = new UOJForm('post_question');
			$post_question->addVTextArea('qcontent', '问题', '', 
				function($content) {
					if (!Auth::check()) {
						return '您尚未登录';
					}
					if (!$content || strlen($content) == 0) {
						return '问题不能为空';
					}
					if (strlen($content) > 140 * 4) {
						return '问题太长';
					}
					return '';
				},
				null
			);
			$post_question->handle = function() {
				global $contest;
				$username = Auth::id();
				DB::insert([
                    "insert into contests_asks",
                    "(contest_id, question, answer, username, post_time, is_hidden)",
                    "values", DB::tuple([$contest['id'], $_POST['qcontent'], '', $username, DB::now(), 1])
                ]);
			};
			$post_question->runAtServer();
		} else {
			$post_question = null;
		}
	} elseif ($cur_tab == 'backstage') {
		if ($is_manager) {
			$post_notice = new UOJForm('post_notice');
			$post_notice->addInput('title', 'text', '标题', '',
				function($title) {
					if (!$title) {
						return '标题不能为空';
					}
					return '';
				},
				null
			);
			$post_notice->addTextArea('content', '正文', '', 
				function($content) {
					if (!$content) {
						return '公告不能为空';
					}
					return '';
				},
				null
			);
			$post_notice->handle = function() {
				global $contest;
				DB::insert([
                    "insert into contests_notice",
                    "(contest_id, title, content, time)",
                    "values", DB::tuple([$contest['id'], $_POST['title'], $_POST['content'], DB::now()])
                ]);
			};
			$post_notice->runAtServer();
		} else {
			$post_notice = null;
		}
		
		if ($is_manager) {
			$reply_question = new UOJForm('reply_question');
			$reply_question->addHidden('rid', '0',
				function($id) {
					if (!validateUInt($id)) {
						return '无效ID';
					}
					$q = DB::selectFirst([
                        "select * from contests_asks",
                        "where", [
                            "id" => $id,
                            "contest_id" => UOJContest::info('id')
                        ]
                    ]);
					if (!$q) {
					    return '无效ID';
					}
					return '';
				},
				null
			);
			$reply_question->addVSelect('rtype', [
				'public' => '公开（如果该问题反复被不同人提出，或指出了题目中的错误，请选择该项）',
				'private' => '非公开',
				'statement' => '请仔细阅读题面（非公开）',
				'no_comment' => '无可奉告（非公开）',
				'no_play' => '请认真比赛（非公开）',
			], '回复类型', 'private');
			$reply_question->addVTextArea('rcontent', '回复', '', 
				function($content) {
				    if (!Auth::check()) {
				        return '您尚未登录';
				    }
				    switch ($_POST['rtype']) {
				    	case 'public':
				    	case 'private':
				    		if (strlen($content) == 0) {
								return '回复不能为空';
							}
							break;
				    }
					return '';
				},
				null
			);
			$reply_question->handle = function() {
				$content = $_POST['rcontent'];
				$is_hidden = 1;
				switch ($_POST['rtype']) {
					case 'statement':
						$content = '请仔细阅读题面';
						break;
					case 'no_comment':
						$content = '无可奉告 ╮(╯▽╰)╭ ';
						break;
					case 'no_play':
						$content = '请认真比赛 (￣口￣)!!';
						break;
					case 'public':
						$is_hidden = 0;
						break;
					default:
						break;
				}
				DB::update([
                    "update contests_asks",
                    "set", [
                        "answer" => $content,
                        "reply_time" => DB::now(),
                        "is_hidden" => $is_hidden
                    ], "where", ["id" => $_POST['rid']]
                ]);
			};
			$reply_question->runAtServer();
		} else {
			$reply_question = null;
		}
	}
	
	function echoDashboard() {
		global $contest, $post_question;
		
		$contest_problems = DB::selectAll([
            "select contests_problems.problem_id, best_ac_submissions.submission_id",
            "from", "contests_problems", "left join", "best_ac_submissions",
            "on", [
                "contests_problems.problem_id" => DB::raw("best_ac_submissions.problem_id"),
                "submitter" => Auth::id()
            ], "where", ["contest_id" => $contest['id']],
            "order by contests_problems.problem_id asc"
        ]);
		
		for ($i = 0; $i < count($contest_problems); $i++) {
            $contest_problems[$i]['problem'] = UOJContestProblem::query($contest_problems[$i]['problem_id']);
            $contest_problems[$i]['problem']->problem_number = $i;
		}
		
		$contest_notice = DB::selectAll([
            "select * from contests_notice",
            "where", ["contest_id" => $contest['id']],
            "order by time desc"
        ]);
		
		if (Auth::check()) {
			$my_questions = DB::selectAll([
                "select * from contests_asks",
                "where", [
                    "contest_id" => $contest['id'],
                    "username" => Auth::id()
                ], "order by post_time desc"
            ]);
			$my_questions_pag = new Paginator([
				'data' => $my_questions
			]);
		} else {
			$my_questions_pag = null;
		}
		
		$others_questions_pag = new Paginator([
			'col_names' => ['*'],
			'table_name' => 'contests_asks',
			'cond' => [
                "contest_id" => $contest['id'],
                Auth::check() ? ["username", "!=", Auth::id()] : ["username", "is not", null],
                "is_hidden" => 0
            ],
			'tail' => 'order by reply_time desc',
			'page_len' => 10
		]);
		
		uojIncludeView('contest-dashboard', [
			'contest' => $contest,
			'contest_notice' => $contest_notice,
			'contest_problems' => $contest_problems,
			'post_question' => $post_question,
			'my_questions_pag' => $my_questions_pag,
			'others_questions_pag' => $others_questions_pag
		]);
	}
	
	function echoBackstage() {
		global $contest, $post_notice, $reply_question;
		
		$questions_pag = new Paginator([
			'col_names' => ['*'],
			'table_name' => 'contests_asks',
			'cond' => ["contest_id" => $contest['id']],
			'tail' => 'order by post_time desc',
			'page_len' => 50
		]);

		if (UOJContest::cur()->managerCanSeeFinalStandingsTab(Auth::user())) {
			$contest_data = queryContestData($contest, ['pre_final' => true]);
			calcStandings($contest, $contest_data, $score, $standings);
			
			$standings_data = [
				'contest' => $contest,
				'standings' => $standings,
				'score' => $score,
				'contest_data' => $contest_data
			];
		} else {
			$standings_data = null;
		}
		
		uojIncludeView('contest-backstage', [
			'contest' => $contest,
			'post_notice' => $post_notice,
			'reply_question' => $reply_question,
			'questions_pag' => $questions_pag,
			'standings_data' => $standings_data
		]);
	}
	
	function echoMySubmissions() {
		$problems = UOJContest::cur()->getProblemIDs();

		$options = [];
		$options[] = ['value' => 'all', 'text' => '所有题目'];
		for ($i = 0; $i < count($problems); $i++) {
			$problem = UOJContestProblem::query($problems[$i]);
			$problem->problem_number = $i;
			$options[] = [
				'value' => $problem->getLetter(),
				'text' => $problem->getTitle(['with' => 'letter', 'simplify' => true]),
			];
		}

		$chosen = UOJRequest::get('p');
		$problem_id = null;
		if (strlen($chosen) == 1) {
			$num = ord($chosen) - ord('A');
			if (0 <= $num && $num < count($problems)) {
				$problem_id = $problems[$num];
			} else {
				$chosen = 'all';
			}
		} else {
			$chosen = 'all';
		}

		$conds = ['contest_id' => UOJContest::info('id')];
		if (Cookie::get('show_all_submissions') === null) {
			$conds += ['submitter' => Auth::id()];
		}
		if ($problem_id !== null) {
			$conds += ['problem_id' => $problem_id];
		}

		uojIncludeView('contest-submissions', [
			'show_all_submissions_status' => Cookie::get('show_all_submissions') !== null,
			'options' => $options,
			'chosen' => $chosen,
			'conds' => $conds
		]);
	}
	
	function echoStandings() {
		global $contest;
		uojIncludeView('contest-standings', ['contest' => $contest] + UOJContest::cur()->queryResult());
	}
	
	function echoStandingsUnfrozen() {
		global $contest;
		
		$contest_data = queryContestData($contest);
		$contest_final_data = queryContestData($contest, ['pre_final' => true]);
		calcStandings($contest, $contest_data, $score, $standings);
		calcStandings($contest, $contest_final_data, $final_score, $final_standings);
		
		uojIncludeView('contest-standings-unfrozen', [
			'contest' => $contest,
			'standings' => $standings,
			'score' => $score,
			'final_score' => $final_score,
			'final_standings' => $final_standings,
			'contest_data' => $contest_data
		]);
	}
	
	function echoContestCountdown() {
		global $contest;
	 	$rest_second = $contest['end_time']->getTimestamp() - UOJTime::$time_now->getTimestamp();
	 	$time_str = UOJTime::$time_now_str;
	 	$contest_ends_in = UOJLocale::get('contests::contest ends in');
	 	echo <<<EOD
		<div class="panel panel-info">
		    <div class="panel-heading">
				<h3 class="panel-title">$contest_ends_in</h3>
			</div>
			<div class="panel-body text-center countdown" data-rest="$rest_second"></div>
		</div>
		EOD;
	}
	
	function echoContestJudgeProgress() {
		$progress = UOJContest::cur()->queryJudgeProgress();
		$title = $progress['title'];
		$rop = $progress['rop'];
		echo <<<EOD
		<div class="panel panel-info">
			<div class="panel-heading">
				<h3 class="panel-title">$title</h3>
			</div>
			<div class="panel-body">
				<div class="progress bot-buffer-no">
					<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="$rop" aria-valuemin="0" aria-valuemax="100" style="width: {$rop}%; min-width: 20px;">{$rop}%</div>
				</div>
			</div>
		</div>
		EOD;
	}
	
	function echoContestFinished() {
		$title = UOJLocale::get('contests::contest ended');
		echo <<<EOD
		<div class="panel panel-info">
			<div class="panel-heading">
				<h3 class="panel-title">$title</h3>
			</div>
		</div>
		EOD;
	}
	
	$page_header = HTML::stripTags($contest['name']) . ' - ';
?>
<?php echoUOJPageHeader(HTML::stripTags($contest['name']) . ' - ' . $tabs_info[$cur_tab]['name'] . ' - ' . UOJLocale::get('contests::contest')) ?>
<div class="text-center">
	<h1><?= $contest['name'] ?></h1>
	<?= ClickZans::getBlock('C', $contest['id'], $contest['zan']) ?>
</div>
<div class="row">
	<?php if ($cur_tab == 'standings' || $cur_tab == 'standings-unfrozen'): ?>
	<div class="col-md-12">
	<?php else: ?>
	<div class="col-md-9">
	<?php endif ?>
		<?= HTML::tablist($tabs_info, $cur_tab) ?>
		<div class="top-buffer-md">
		<?php
			if ($cur_tab == 'dashboard') {
				echoDashboard();
			} elseif ($cur_tab == 'submissions') {
				echoMySubmissions();
			} elseif ($cur_tab == 'standings') {
				echoStandings();
			} elseif ($cur_tab == 'backstage') {
				echoBackstage();
			} elseif ($cur_tab == 'standings-unfrozen') {
				echoStandingsUnfrozen();
			}
		?>
		</div>
	</div>
	
	<?php if ($cur_tab == 'standings' || $cur_tab == 'standings-unfrozen'): ?>
	<div class="col-md-12">
		<hr />
	</div>
	<?php endif ?>

	<div class="col-md-3">
		<?php
			if ($contest['cur_progress'] <= CONTEST_IN_PROGRESS) {
				echoContestCountdown();
			} else if ($contest['cur_progress'] <= CONTEST_TESTING) {
				echoContestJudgeProgress();
			} else {
				echoContestFinished();
			}
		?>
		<?php if ($cur_tab == 'standings' || $cur_tab == 'standings-unfrozen'): ?>
	</div>
	<div class="col-md-3">
	<?php endif ?>
		<?php if ($contest['extra_config']['basic_rule'] === 'UOJ-OI'): ?>
		<p>此次比赛为OI赛制。</p>
		<p><strong>注意：比赛时只显示测样例的结果。</strong></p>
		<?php elseif ($contest['extra_config']['basic_rule'] === 'UOJ-ACM'): ?>
	    <p>此次比赛为ACM赛制。</p>
		<p><strong>封榜时间：<?=$contest['frozen_time']->format('Y-m-d H:i:s')?></strong></p>
		<?php elseif ($contest['extra_config']['basic_rule'] === 'UOJ-IOI'): ?>
		<p>此次比赛为IOI赛制。</p>
		<p>比赛时显示的得分即最终得分。</p>
	    <?php endif ?>
	
		<a href="/contest/<?=$contest['id']?>/registrants" class="btn btn-info btn-block"><?= UOJLocale::get('contests::contest registrants') ?></a>
		<?php if (isSuperUser($myUser)): ?>
		<a href="/contest/<?=$contest['id']?>/manage" class="btn btn-primary btn-block">管理</a>
		<?php if (isset($start_test_form)): ?>
		<div class="top-buffer-sm">
			<?php $start_test_form->printHTML(); ?>
		</div>
		<?php endif ?>
		<?php if (isset($publish_result_form)): ?>
		<div class="top-buffer-sm">
			<?php $publish_result_form->printHTML(); ?>
		</div>
		<?php endif ?>
		<?php endif ?>
	
		<?php if (!empty($contest['extra_config']['links'])): ?>
			<?php if ($cur_tab == 'standings' || $cur_tab == 'standings-unfrozen'): ?>
	</div>
	<div class="col-md-3">
		<div class="panel panel-info">
			<?php else: ?>
		<div class="panel panel-info top-buffer-lg">
			<?php endif ?>
			<div class="panel-heading">
				<h3 class="panel-title">比赛资料</h3>
			</div>
			<div class="list-group">
			<?php foreach ($contest['extra_config']['links'] as $link): ?>
				<a href="/blog/<?=$link[1]?>" class="list-group-item"><?=$link[0]?></a>
			<?php endforeach ?>
			</div>
		</div>
		<?php endif ?>
	</div>
</div>
<?php echoUOJPageFooter() ?>
