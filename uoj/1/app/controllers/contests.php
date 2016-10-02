<?php
	requirePHPLib('form');
	
	$upcoming_contest_name = null;
	$upcoming_contest_href = null;
	$rest_second = 1000000;
	function echoContest($contest) {
		global $myUser, $upcoming_contest_name, $upcoming_contest_href, $rest_second;
		
		$contest_name_link = <<<EOD
<a href="/contest/{$contest['id']}">{$contest['name']}</a>
EOD;
		genMoreContestInfo($contest);
		if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
			$cur_rest_second = $contest['start_time']->getTimestamp() - UOJTime::$time_now->getTimestamp();
			if ($cur_rest_second < $rest_second) {
				$upcoming_contest_name = $contest['name'];
				$upcoming_contest_href = "/contest/{$contest['id']}";
				$rest_second = $cur_rest_second;
			}
			if ($myUser != null && hasRegistered($myUser, $contest)) {
				$contest_name_link .= '<sup><a style="color:green">'.UOJLocale::get('contests::registered').'</a></sup>';
			} else {
				$contest_name_link .= '<sup><a style="color:red" href="/contest/'.$contest['id'].'/register">'.UOJLocale::get('contests::register').'</a></sup>';
			}
		} elseif ($contest['cur_progress'] == CONTEST_IN_PROGRESS) {
			$contest_name_link .= '<sup><a style="color:blue" href="/contest/'.$contest['id'].'">'.UOJLocale::get('contests::in progress').'</a></sup>';
		} elseif ($contest['cur_progress'] == CONTEST_PENDING_FINAL_TEST) {
			$contest_name_link .= '<sup><a style="color:blue" href="/contest/'.$contest['id'].'">'.UOJLocale::get('contests::pending final test').'</a></sup>';
		} elseif ($contest['cur_progress'] == CONTEST_TESTING) {
			$contest_name_link .= '<sup><a style="color:blue" href="/contest/'.$contest['id'].'">'.UOJLocale::get('contests::final testing').'</a></sup>';
		} elseif ($contest['cur_progress'] == CONTEST_FINISHED) {
			$contest_name_link .= '<sup><a style="color:grey" href="/contest/'.$contest['id'].'/standings">'.UOJLocale::get('contests::ended').'</a></sup>';
		}
		
		$last_hour = round($contest['last_min'] / 60, 2);
		
		$click_zan_block = getClickZanBlock('C', $contest['id'], $contest['zan']);
		echo '<tr>';
		echo '<td>', $contest_name_link, '</td>';
		echo '<td>', '<a href="'.HTML::timeanddate_url($contest['start_time'], array('duration' => $contest['last_min'])).'">'.$contest['start_time_str'].'</a>', '</td>';
		echo '<td>', UOJLocale::get('hours', $last_hour), '</td>';
		echo '<td>', '<a href="/contest/'.$contest['id'].'/registrants"><span class="glyphicon glyphicon-user"></span> &times;'.$contest['player_num'].'</a>', '</td>';
		echo '<td>', '<div class="text-left">'.$click_zan_block.'</div>', '</td>';
		echo '</tr>';
	}
?>
<?php echoUOJPageHeader(UOJLocale::get('contests')) ?>
<h4><?= UOJLocale::get('contests::current or upcoming contests') ?></h4>
<?php
	$table_header = '';
	$table_header .= '<tr>';
	$table_header .= '<th>'.UOJLocale::get('contests::contest name').'</th>';
	$table_header .= '<th style="width:15em;">'.UOJLocale::get('contests::start time').'</th>';
	$table_header .= '<th style="width:100px;">'.UOJLocale::get('contests::duration').'</th>';
	$table_header .= '<th style="width:100px;">'.UOJLocale::get('contests::the number of registrants').'</th>';
	$table_header .= '<th style="width:180px;">'.UOJLocale::get('appraisal').'</th>';
	$table_header .= '</tr>';
	echoLongTable(array('*'), 'contests', "status != 'finished'", 'order by id desc', $table_header,
		echoContest,
		array('page_len' => 100)
	);

	if ($rest_second <= 86400) {
		echo <<<EOD
<div class="text-center bot-buffer-lg">
<div class="text-warning">$upcoming_contest_name 倒计时</div>
<div id="contest-countdown"></div>
<script type="text/javascript">
$('#contest-countdown').countdown($rest_second, function() {
	if (confirm('$upcoming_contest_name 已经开始了。是否要跳转到比赛页面？')) {
		window.location.href = "$upcoming_contest_href";
	}
});
</script>
</div>
EOD;
	}
?>

<h4><?= UOJLocale::get('contests::ended contests') ?></h4>
<?php
	echoLongTable(array('*'), 'contests', "status = 'finished'", 'order by id desc', $table_header,
		echoContest,
		array('page_len' => 100,
			'print_after_table' => function() {
				global $myUser;
				if (isSuperUser($myUser)) {
					echo '<div class="text-right">';
					echo '<a href="/contest/new" class="btn btn-primary">'.UOJLocale::get('contests::add new contest').'</a>';
					echo '</div>';
				}
			}
		)
	);
?>
<?php echoUOJPageFooter() ?>
