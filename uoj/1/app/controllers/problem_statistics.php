<?php
	if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
		become404Page();
	}
	
	$contest = validateUInt($_GET['contest_id']) ? queryContest($_GET['contest_id']) : null;
	if ($contest != null) {
		genMoreContestInfo($contest);
		if (!isContestProblemVisibleToUser($problem, $contest, $myUser)) {
			become404Page();
		}
	} else {
		if (!isProblemVisibleToUser($problem, $myUser)) {
			become404Page();
		}
	}

	function scoreDistributionData() {
		$data = array();
		$result = DB::select("select score, count(*) from submissions where problem_id = {$_GET['id']} and score is not null group by score");
		$is_res_empty = true;
		$has_score_0 = false;
		$has_score_100 = false;
		while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
			if ($row[0] == 0) {
				$has_score_0 = true;
			} else if ($row[0] == 100) {
				$has_score_100 = true;
			}
			$score = $row[0] * 100;
			$data[] = array('score' => $score, 'count' => $row[1]);
		}
		if (!$has_score_0) {
			array_unshift($data, array('score' => 0, 'count' => 0));
		}
		if (!$has_score_100) {
			$data[] = array('score' => 10000, 'count' => 0);
		}
		return $data;
	}
	
	$data = scoreDistributionData();
	$pre_data = $data;
	$suf_data = $data;
	for ($i = 0; $i < count($data); $i++) {
		$data[$i]['score'] /= 100;
	}
	for ($i = 1; $i < count($data); $i++) {
		$pre_data[$i]['count'] += $pre_data[$i - 1]['count'];
	}
	for ($i = count($data) - 1; $i > 0; $i--) {
		$suf_data[$i - 1]['count'] += $suf_data[$i]['count'];
	}
	
	$submissions_sort_by_choice = !isset($_COOKIE['submissions-sort-by-code-length']) ? 'time' : 'tot_size';
?>
<?php
	$REQUIRE_LIB['morris'] = "";
?>
<?php echoUOJPageHeader(HTML::stripTags($problem['title']) . ' - ' . UOJLocale::get('problems::statistics')) ?>

<h1 class="page-header text-center"><?= $problem['title'] ?> <?= UOJLocale::get('problems::statistics') ?></h1>

<?php if ($contest && !hasContestPermission($myUser, $contest) && $contest['cur_progress'] <= CONTEST_IN_PROGRESS): ?>
<h2 class="text-center text-muted">比赛尚未结束</h2>
<?php else: ?>
<h2 class="text-center"><?= UOJLocale::get('problems::accepted submissions') ?></h2>
<div class="text-right bot-buffer-sm">
	<div class="btn-group btn-group-sm">
		<a href="<?=$SERVER['REQUEST_URI']?>" class="<?=$submissions_sort_by_choice == 'time' ? 'btn btn-info btn-xs active' : 'btn btn-info btn-xs'?>" id="submissions-sort-by-run-time"><?= UOJLocale::get('problems::fastest') ?></a>
		<a href="<?=$SERVER['REQUEST_URI']?>" class="<?=$submissions_sort_by_choice == 'tot_size' ? 'btn btn-info btn-xs active' : 'btn btn-info btn-xs'?>" id="submissions-sort-by-code-length"><?= UOJLocale::get('problems::shortest') ?></a>
	</div>
</div>

<script type="text/javascript">
	$('#submissions-sort-by-run-time').click(function() {
		$.cookie('submissions-sort-by-run-time', '');
		$.removeCookie('submissions-sort-by-code-length');
	});
	$('#submissions-sort-by-code-length').click(function() {
		$.cookie('submissions-sort-by-code-length', '');
		$.removeCookie('submissions-sort-by-run-time');
	});
</script>

<?php if ($submissions_sort_by_choice == 'time'): ?>
	<?php echoSubmissionsList("best_ac_submissions.submission_id = submissions.id and best_ac_submissions.problem_id = {$problem['id']}", 'order by best_ac_submissions.used_time, best_ac_submissions.used_memory, best_ac_submissions.tot_size', array('judge_time_hidden' => '', 'table_name' => 'best_ac_submissions, submissions'), $myUser); ?>
<?php else: ?>
	<?php echoSubmissionsList("best_ac_submissions.shortest_id = submissions.id and best_ac_submissions.problem_id = {$problem['id']}", 'order by best_ac_submissions.shortest_tot_size, best_ac_submissions.shortest_used_time, best_ac_submissions.shortest_used_memory', array('judge_time_hidden' => '', 'table_name' => 'best_ac_submissions, submissions'), $myUser); ?>
<?php endif ?>

<h2 class="text-center"><?= UOJLocale::get('problems::score distribution') ?></h2>
<div id="score-distribution-chart" style="height: 250px;"></div>
<script type="text/javascript">
new Morris.Bar({
	element: 'score-distribution-chart',
	data: <?= json_encode($data) ?>,
	barColors: function(r, s, type) {
		return getColOfScore(r.label);
	},
	xkey: 'score',
	ykeys: ['count'],
	labels: ['number'],
	hoverCallback: function(index, options, content, row) {
		var scr = row.score;
		return '<div class="morris-hover-row-label">' + 'score: ' + scr + '</div>' +
			'<div class="morris-hover-point">' + '<a href="/submissions?problem_id=' + <?= $problem['id'] ?> + '&amp;min_score=' + scr + '&amp;max_score=' + scr + '">' + 'number: ' + row.count + '</a>' + '</div>';
	},
	resize: true
});
</script>

<h2 class="text-center"><?= UOJLocale::get('problems::prefix sum of score distribution') ?></h2>
<div id="score-distribution-chart-pre" style="height: 250px;"></div>
<script type="text/javascript">
new Morris.Line({
	element: 'score-distribution-chart-pre',
	data: <?= json_encode($pre_data) ?>,
	xkey: 'score',
	ykeys: ['count'],
	labels: ['number'],
	lineColors: function(row, sidx, type) {
		if (type == 'line') {
			return '#0b62a4';
		}
		return getColOfScore(row.src.score / 100);
	},
	xLabelFormat: function(x) {
		return (x.getTime() / 100).toString();
	},
	hoverCallback: function(index, options, content, row) {
		var scr = row.score / 100;
		return '<div class="morris-hover-row-label">' + 'score: &le;' + scr + '</div>' +
			'<div class="morris-hover-point">' + '<a href="/submissions?problem_id=' + <?= $problem['id'] ?> + '&amp;max_score=' + scr + '">' + 'number: ' + row.count + '</a>' + '</div>';
	},
	resize: true
});
</script>

<h2 class="text-center"><?= UOJLocale::get('problems::suffix sum of score distribution') ?></h2>
<div id="score-distribution-chart-suf" style="height: 250px;"></div>
<script type="text/javascript">
new Morris.Line({
	element: 'score-distribution-chart-suf',
	data: <?= json_encode($suf_data) ?>,
	xkey: 'score',
	ykeys: ['count'],
	labels: ['number'],
	lineColors: function(row, sidx, type) {
		if (type == 'line') {
			return '#0b62a4';
		}
		return getColOfScore(row.src.score / 100);
	},
	xLabelFormat: function(x) {
		return (x.getTime() / 100).toString();
	},
	hoverCallback: function(index, options, content, row) {
		var scr = row.score / 100;
		return '<div class="morris-hover-row-label">' + 'score: &ge;' + scr + '</div>' +
			'<div class="morris-hover-point">' + '<a href="/submissions?problem_id=' + <?= $problem['id'] ?> + '&amp;min_score=' + scr + '">' + 'number: ' + row.count + '</a>' + '</div>';
	},
	resize: true
});
</script>

<?php endif ?>

<?php echoUOJPageFooter() ?>
