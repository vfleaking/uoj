<?php
	requirePHPLib('form');
	
	if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
		become404Page();
	}
	genMoreContestInfo($contest);

	if (!hasContestPermission($myUser, $contest)) {
		if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
			header("Location: /contest/{$contest['id']}/register");
			die();
		} elseif ($contest['cur_progress'] == CONTEST_IN_PROGRESS) {
			if ($myUser == null || !hasRegistered($myUser, $contest)) {
				becomeMsgPage("<h1>比赛正在进行中</h1><p>很遗憾，您尚未报名。比赛结束后再来看吧～</p>");
			}
		}
	}

	if (isset($_POST['check_notice'])) {
		$result = mysql_query("select * from contests_notice where contest_id = '${contest['id']}' order by time desc limit 1");
		try {
			while ($row = mysql_fetch_array($result)) {
				if (new DateTime($row['time']) > new DateTime($_POST['last_time'])) {
					die(json_encode(array('msg' => $row['title'] . ' : ' . $row['content'], 'time' => UOJTime::$time_now_str)));
				}
			}
		} catch (Exception $e) {
		}
		die(json_encode(array('time' => UOJTime::$time_now_str)));
	}
	
	if (isset($_GET['tab'])) {
		$cur_tab = $_GET['tab'];
	} else {
		$cur_tab = 'dashboard';
	}

	// problems: pos => id
	// data    : id, submit_time, submitter, problem_pos, score
	// people  : username, user_rating
	function queryContestData() {
		global $contest;
		$problems = array();
		$prob_pos = array();
		$n_problems = 0;
		$result = mysql_query("select problem_id from contests_problems where contest_id = ${contest['id']} order by problem_id");
		while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
			$prob_pos[$problems[] = (int)$row[0]] = $n_problems++;
		}
		
		$data = array();
		if ($contest['cur_progress'] < CONTEST_FINISHED) {
			$result = mysql_query("select id, submit_time, submitter, problem_id, score from submissions where contest_id = {$contest['id']} and score is not null order by id");
		} else {
			$result = mysql_query("select submission_id, date_add('{$contest['start_time_str']}', interval penalty second), submitter, problem_id, score from contests_submissions where contest_id = {$contest['id']}");
		}
		while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
			$row[0] = (int)$row[0];
			$row[3] = $prob_pos[$row[3]];
			$row[4] = (int)$row[4];
			$data[] = $row;
		}
		
		$people = array();
		$result = mysql_query("select username, user_rating from contests_registrants where contest_id = {$contest['id']} and has_participated = 1");
		while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
			$row[1] = (int)$row[1];
			$people[] = $row;
		}

		return array('problems' => $problems, 'data' => $data, 'people' => $people);
	}

	function calcStandings($contest_data, &$score, &$standings, $update_contests_submissions = false) {
		global $contest;
		
		// score: username, problem_pos => score, penalty, id
		$score = array();
		$n_people = count($contest_data['people']);
		$n_problems = count($contest_data['problems']);
		foreach ($contest_data['people'] as $person) {
			$score[$person[0]] = array();
		}
		foreach ($contest_data['data'] as $submission) {		
			$penalty = (new DateTime($submission[1]))->getTimestamp() - $contest['start_time']->getTimestamp();
			if ($contest['extra_config']['standings_version'] >= 2) {
				if ($submission[4] == 0) {
					$penalty = 0;
				}
			}
			$score[$submission[2]][$submission[3]] = array($submission[4], $penalty, $submission[0]);
		}

		// standings: rank => score, penalty, [username, user_rating], virtual_rank
		$standings = array();
		foreach ($contest_data['people'] as $person) {
			$cur = array(0, 0, $person);
			for ($i = 0; $i < $n_problems; $i++) {
				if (isset($score[$person[0]][$i])) {
					$cur_row = $score[$person[0]][$i];
					$cur[0] += $cur_row[0];
					$cur[1] += $cur_row[1];
					if ($update_contests_submissions) {
						DB::insert("insert into contests_submissions (contest_id, submitter, problem_id, submission_id, score, penalty) values ({$contest['id']}, '{$person[0]}', {$contest_data['problems'][$i]}, {$cur_row[2]}, {$cur_row[0]}, {$cur_row[1]})");
					}
				}
			}
			$standings[] = $cur;
		}

		usort($standings, function($lhs, $rhs) {
			if ($lhs[0] != $rhs[0]) {
				return $rhs[0] - $lhs[0];
			} else if ($lhs[1] != $rhs[1]) {
				return $lhs[1] - $rhs[1];
			} else {
				return strcmp($lhs[2][0], $rhs[2][0]);
			}
		});

		$is_same_rank = function($lhs, $rhs) {
			return $lhs[0] == $rhs[0] && $lhs[1] == $rhs[1];
		};

		for ($i = 0; $i < $n_people; $i++) {
			if ($i == 0 || !$is_same_rank($standings[$i - 1], $standings[$i])) {
				$standings[$i][] = $i + 1;
			} else {
				$standings[$i][] = $standings[$i - 1][3];
			}
		}
	}
	
	if (isSuperUser($myUser)) {
		if (CONTEST_PENDING_FINAL_TEST <= $contest['cur_progress'] && $contest['cur_progress'] <= CONTEST_TESTING) {
			$start_test_form = new UOJForm('start_test');
			$start_test_form->handle = function() {
				global $contest;
				$result = mysql_query("select id, problem_id, content from submissions where contest_id = {$contest['id']}");
				while ($submission = mysql_fetch_array($result, MYSQL_ASSOC)) {
					if (!isset($contest['extra_config']["problem_{$submission['problem_id']}"])) {
	 					$content = json_decode($submission['content'], true);
						if (isset($content['final_test_config'])) {
							$content['config'] = $content['final_test_config'];
							unset($content['final_test_config']);
						}
						if (isset($content['first_test_config'])) {
							unset($content['first_test_config']);
						}
						$esc_content = mysql_real_escape_string(json_encode($content));
						DB::update("update submissions set judge_time = NULL, result = '', score = NULL, status = 'Waiting Rejudge', content = '$esc_content' where id = {$submission['id']}");
					}
				}
				mysql_query("update contests set status = 'testing' where id = {$contest['id']}");
			};
			$start_test_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
			$start_test_form->submit_button_config['smart_confirm'] = '';
			if ($contest['cur_progress'] < CONTEST_TESTING) {
				$start_test_form->submit_button_config['text'] = '开始最终测试';
			} else {
				$start_test_form->submit_button_config['text'] = '重新开始最终测试';
			}

			$start_test_form->runAtServer();
		}
		if ($contest['cur_progress'] >= CONTEST_TESTING) {
			$publish_result_form = new UOJForm('publish_result');
			$publish_result_form->handle = function() {
				// time config
				set_time_limit(0);
				ignore_user_abort(true);

				global $contest;
				$contest_data = queryContestData();
				calcStandings($contest_data, $score, $standings, true);
				if (!isset($contest['extra_config']['unrated'])) {
					$rating_k = isset($contest['extra_config']['rating_k']) ? $contest['extra_config']['rating_k'] : 400;
					$ratings = calcRating($standings, $rating_k);
				} else {
					$ratings = array();
					for ($i = 0; $i < count($standings); $i++) {
						$ratings[$i] = $standings[$i][2][1];
					}
				}

				for ($i = 0; $i < count($standings); $i++) {
					$user = queryUser($standings[$i][2][0]);
					$change = $ratings[$i] - $user['rating'];
					$user_link = getUserLink($user['username']);

					if ($change != 0) {
						$tail = '<strong style="color:red">' . ($change > 0 ? '+' : '') . $change . '</strong>';
						$content = <<<EOD
<p>${user_link} 您好：</p>
<p class="indent2">您在 <a href="/contest/{$contest['id']}">{$contest['name']}</a> 这场比赛后的Rating变化为${tail}，当前Rating为 <strong style="color:red">{$ratings[$i]}</strong>。</p>
EOD;
					} else {
						$content = <<<EOD
<p>${user_link} 您好：</p>
<p class="indent2">您在 <a href="/contest/{$contest['id']}">{$contest['name']}</a> 这场比赛后Rating没有变化。当前Rating为 <strong style="color:red">{$ratings[$i]}</strong>。</p>
EOD;
					}
					sendSystemMsg($user['username'], 'Rating变化通知', $content);
					mysql_query("update user_info set rating = {$ratings[$i]} where username = '{$standings[$i][2][0]}'");
					mysql_query("update contests_registrants set rank = {$standings[$i][3]} where contest_id = {$contest['id']} and username = '{$standings[$i][2][0]}'");
				}
				mysql_query("update contests set status = 'finished' where id = {$contest['id']}");
			};
			$publish_result_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
			$publish_result_form->submit_button_config['smart_confirm'] = '';
			$publish_result_form->submit_button_config['text'] = '公布成绩';
			
			$publish_result_form->runAtServer();
		}
	}
	
	function echoDashboard() {
		global $myUser, $contest, $post_notice;
		
		echo '<div class="table-responsive">';
		echo '<table class="table table-bordered table-hover table-striped table-text-center">';
		echo '<thead>';
		echo '<th style="width:5em">#</th>';
		echo '<th>', UOJLocale::get('problems::problem'), '</th>';
		echo '</thead>';
		echo '<tbody>';
		$contest_problems = DB::selectAll("select contests_problems.problem_id, best_ac_submissions.submission_id from contests_problems left join best_ac_submissions on contests_problems.problem_id = best_ac_submissions.problem_id and submitter = '{$myUser['username']}' where contest_id = {$contest['id']} order by contests_problems.problem_id asc");
		for ($i = 0; $i < count($contest_problems); $i++) {
			$problem = queryProblemBrief($contest_problems[$i]['problem_id']);
			echo '<tr>';
			if ($contest_problems[$i]['submission_id']) {
				echo '<td class="success">';
			} else {
				echo '<td>';
			}
			echo chr(ord('A') + $i), '</td>';
			echo '<td>', getContestProblemLink($problem, $contest['id']), '</td>';
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
		echo '</div>';
		
		echo '<h3>', UOJLocale::get('contests::contest notice'), '</h3>';
		$header = '';
		$header .= '<tr>';
		$header .= '<th style="width:10em">'.UOJLocale::get('title').'</th>';
		$header .= '<th>'.UOJLocale::get('content').'</th>';
		$header .= '<th style="width:12em">'.UOJLocale::get('time').'</th>';
		$header .= '</tr>';
		echoLongTable(array('*'), 'contests_notice', "contest_id = '{$contest['id']}'", "order by time desc", $header,
			function($notice) {
				echo '<tr>';
				echo '<td>', HTML::escape($notice['title']), '</td>';
				echo '<td style="white-space:pre-wrap; text-align: left">', $notice['content'], '</td>';
				echo '<td>', $notice['time'], '</td>';
				echo '</tr>';
			},
			array(
				'table_classes' => array('table', 'table-bordered', 'table-hover', 'table-striped', 'table-vertical-middle', 'table-text-center'),
				'echo_full' => true
			)
		);
		
		if (isSuperUser(Auth::user())) {
			echo '<div class="text-center">';
			echo '<button id="button-display-post-notice" type="button" class="btn btn-danger btn-xs">发布比赛公告</button>';
			echo '</div>';
			echo '<div id="div-form-post-notice" style="display:none" class="bot-buffer-md">';
			$post_notice->printHTML();
			echo '</div>';
			echo <<<EOD
<script type="text/javascript">
$(document).ready(function() {
	$('#button-display-post-notice').click(function() {
		$('#div-form-post-notice').toggle('fast');
	});
});
</script>
EOD;
		}
	}
	
	function echoMySubmissions() {
		global $contest, $myUser;

		$show_all_submissions_status = Cookie::get('show_all_submissions') !== null ? 'checked="checked" ' : '';
		$show_all_submissions = UOJLocale::get('contests::show all submissions');
		echo <<<EOD
			<div class="checkbox text-right">
				<label for="input-show_all_submissions"><input type="checkbox" id="input-show_all_submissions" $show_all_submissions_status/> $show_all_submissions</label>
			</div>
			<script type="text/javascript">
				$('#input-show_all_submissions').click(function() {
					if (this.checked) {
						$.cookie('show_all_submissions', '');
					} else {
						$.removeCookie('show_all_submissions');
					}
					location.reload();
				});
			</script>
EOD;
		if (Cookie::get('show_all_submissions') !== null) {
			echoSubmissionsList("contest_id = {$contest['id']}", 'order by id desc', array('judge_time_hidden' => ''), $myUser);
		} else {
			echoSubmissionsList("submitter = '{$myUser['username']}' and contest_id = {$contest['id']}", 'order by id desc', array('judge_time_hidden' => ''), $myUser);
		}
	}
	
	function echoStandings() {
		global $contest;
		
		$contest_data = queryContestData();
		calcStandings($contest_data, $score, $standings);

		echo '<div id="standings">';
		echo '</div>';

		/*
		echo '<div class="table-responsive">';
		echo '<table id="standings-table" class="table table-bordered table-striped table-text-center table-vertical-middle">';
		echo '</table>';
		echo '</div>';
		 */

		echo '<script type="text/javascript">';
		echo 'standings_version=', $contest['extra_config']['standings_version'], ';';
		echo 'contest_id=', $contest['id'], ';';
		echo 'standings=', json_encode($standings), ';';
		echo 'score=', json_encode($score), ';';
		echo 'problems=', json_encode($contest_data['problems']), ';';
		echo '$(document).ready(showStandings());';
		echo '</script>';
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
		<script type="text/javascript">
			checkContestNotice({$contest['id']}, '$time_str');
		</script>
EOD;
	}
	
	function echoContestJudgeProgress() {
		global $contest;
		if ($contest['cur_progress'] < CONTEST_TESTING) {
			$rop = 0;
			$title = UOJLocale::get('contests::contest pending final test');
		} else {
			$total = DB::selectCount("select count(*) from submissions where contest_id = {$contest['id']}");
			$n_judged = DB::selectCount("select count(*) from submissions where contest_id = {$contest['id']} and status = 'Judged'");
			$rop = $total == 0 ? 100 : (int)($n_judged / $total * 100);
			$title = UOJLocale::get('contests::contest final testing');
		}
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
		$title = DB::escape($_POST['title']);
		$content = DB::escape($_POST['content']);
		mysql_query("insert into contests_notice (contest_id, title, content, time) values ('{$contest['id']}', '$title', '$content', now())");
	};
	$post_notice->runAtServer();
	
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
	
	if (!isset($tabs_info[$cur_tab])) {
		become404Page();
	}
	
	$page_header = HTML::stripTags($contest['name']) . ' - ';
?>
<?php echoUOJPageHeader(HTML::stripTags($contest['name']) . ' - ' . $tabs_info[$cur_tab]['name'] . ' - ' . UOJLocale::get('contests::contest')) ?>
<div class="text-center">
	<h1><?= $contest['name'] ?></h1>
	<?= getClickZanBlock('C', $contest['id'], $contest['zan']) ?>
</div>
<div class="row">
	<?php if ($cur_tab == 'standings'): ?>
	<div class="col-sm-12">
	<?php else: ?>
	<div class="col-sm-9">
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
			}
		?>
		</div>
	</div>
	
	<?php if ($cur_tab == 'standings'): ?>
	<div class="col-sm-12">
		<hr />
	</div>
	<?php endif ?>

	<div class="col-sm-3">
		<?php
			if ($contest['cur_progress'] <= CONTEST_IN_PROGRESS) {
				echoContestCountdown();
			} else if ($contest['cur_progress'] <= CONTEST_TESTING) {
				echoContestJudgeProgress();
			} else {
				echoContestFinished();
			}
		?>
		<?php if ($cur_tab == 'standings'): ?>
	</div>
	<div class="col-sm-3">
	<?php endif ?>
		<p>此次比赛为OI赛制。</p>
		<p><strong>注意：比赛时只显示测样例的结果。</strong></p>
	
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
	
		<?php if ($contest['extra_config']['links']) { ?>
			<?php if ($cur_tab == 'standings'): ?>
	</div>
	<div class="col-sm-3">
		<div class="panel panel-info">
		<?php else: ?>
		<div class="panel panel-info top-buffer-lg">
		<?php endif ?>
			<div class="panel-heading">
				<h3 class="panel-title">比赛资料</h3>
			</div>
			<div class="list-group">
			<?php foreach ($contest['extra_config']['links'] as $link) { ?>
				<a href="/blog/<?=$link[1]?>" class="list-group-item"><?=$link[0]?></a>
			<?php } ?>
			</div>
		</div>
		<?php } ?>
	</div>
</div>
<?php echoUOJPageFooter() ?>
