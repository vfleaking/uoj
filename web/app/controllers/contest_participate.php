<?php

UOJContest::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJProblem::init(UOJRequest::get('problem_id')) || UOJResponse::page404();
UOJProblem::upgradeToContestProblem() || UOJResponse::page404();

$contest = UOJContest::info();

UOJContest::cur()->userCanParticipateNow(Auth::user()) || UOJResponse::page403();
!UOJContest::cur()->userHasMarkedParticipated(Auth::user()) || redirectTo(UOJContest::cur()->getUri());

$need_to_confirm = UOJContest::cur()->registrantNeedToConfirmParticipation(Auth::user());

if (!$need_to_confirm) {
	UOJContest::cur()->markUserAsParticipated(Auth::user());
} elseif (isset($_POST['participate'])) {
	if (UOJContest::cur()->markUserAsParticipated(Auth::user())) {
		die('ok');
	} else {
		die('failed');
	}
}

$go_uri = UOJProblem::cur()->getUri();
$default_goback_uri = UOJContest::cur()->getUri();

$function_def = <<<EOD
function go() {
	window.location.href = '{$go_uri}';
}
function goBack() {
	var prevUrl = document.referrer;
	if (/\/problem\//.test(prevUrl) || /\/confirm-participation/.test(prevUrl)) {
		prevUrl = '{$default_goback_uri}';
	};
	window.location.href = prevUrl;
}
EOD;

?>

<?php if (!$need_to_confirm): ?>
<!DOCTYPE html>
<html>
	<body>
		<script type="text/javascript">
			<?= $function_def ?>

			go();
		</script>
	</body>
</html>
<?php else: ?>
<?php echoUOJPageHeader(HTML::stripTags(UOJContest::info('name')) . ' - 确认参赛') ?>
<div class="row">
    <div class="col-lg-offset-1 col-lg-10">
		<h1 class="page-header">确认参赛：<?= UOJContest::info('name') ?></h1>
		<ul>
			<li>比赛报名后不算正式参赛，报名后进了比赛页面也不算参赛，<strong>看了题目才算正式参赛</strong>。如果未正式参赛则不算 rating。</li>
			<li>你正在试图打开一道比赛题目：<?= UOJProblem::cur()->getLink(['with' => 'letter']) ?>，虽然你迟到了一点点。</li>
			<li>如果看了题目却不好好打的话，可能会掉 rating 哦~</li>
		</ul>
		<div class="text-right">
			<button id="button-ok" class="btn btn-primary">懂了，这就参赛</button>
			<button id="button-cancel" class="btn btn-default">溜了溜了</button>
		</div>
	</div>
</div>
<script type="text/javascript">
<?= $function_def ?>

$(document).ready(function() {
	$('#button-ok').click(function(e) {
		e.preventDefault();
		$.post(window.location.href, {
			_token : "<?= crsf_token() ?>",
			participate: ''
		}, function(msg) {
			if (msg == 'ok') {
				go();
			}
		});
	});
	$('#button-cancel').click(function(e) {
		goBack();
	});
});
</script>
<?php echoUOJPageFooter() ?>
<?php endif ?>