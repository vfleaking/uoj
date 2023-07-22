<?php

requirePHPLib('judger');

UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();

$problem = UOJProblem::cur()->info;
$problem_presentation = UOJProblem::cur()->queryPresentationInfo();

if (UOJRequest::get('contest_id')) {
	UOJContest::init(UOJRequest::get('contest_id')) || UOJResponse::page404();
	UOJProblem::upgradeToContestProblem() || UOJResponse::page404();
}
UOJProblem::cur()->userCanView(Auth::user(), ['ensure' => true]);

$is_participating = false;
$no_more_submission = false;
$submission_error = null; // if not null, show an error and disable submission
$submission_warning = null; // if not null, show a warning, but the user can still submit

if (UOJContest::cur() && UOJContest::cur()->userCanParticipateNow(Auth::user())) {
	if (!UOJContest::cur()->userHasMarkedParticipated(Auth::user())) {
		if (UOJContest::cur()->registrantNeedToConfirmParticipation(Auth::user())) {
			redirectTo(UOJContest::cur()->getUri("/confirm-participation?problem_id={$problem['id']}"));
		}
		UOJContest::cur()->markUserAsParticipated(Auth::user());
	}
	$is_participating = true;
}

$pre_submit_check_ret = UOJProblem::cur()->preSubmitCheck();

if ($is_participating && $pre_submit_check_ret === true) {
	// check if the user can submit in the contest
	$submit_time_limit = UOJContestProblem::cur()->submitTimeLimit();
	$max_cnt = UOJContest::cur()->maxSubmissionCountPerProblem();
	if ($submit_time_limit != -1) {
		$cur_contest_time = (UOJTime::$time_now->getTimestamp() - UOJContest::info('start_time')->getTimestamp()) / 60;
		if ($cur_contest_time > $submit_time_limit) {
			$no_more_submission = "本题只能在比赛的前 {$submit_time_limit} 分钟提交，没法再交咯";
		}
	}
	if (!$no_more_submission) {
		if ($max_cnt != -1) {
			$cnt = UOJContestProblem::cur()->queryUserSubmissionCountInContest(Auth::user());
			if ($cnt >= $max_cnt) {
				$no_more_submission = "提交次数已达到 {$cnt} 次，没法再交咯";
			}
		}
	}

	// set submission warning
	if (!$no_more_submission) {
		if ($max_cnt != -1) {
			$warning1 = "已使用 {$cnt}/{$max_cnt} 次提交机会";
		} else {
			$warning1 = null;
		}
		if ($submit_time_limit != -1) {
			$warning2 = "注意本题只能在比赛的前 {$submit_time_limit} 分钟提交";
		} else {
			$warning2 = null;
		}
		if ($warning1 && $warning2) {
			$submission_warning = "{$warning1}（{$warning2}）";
		} else {
			$submission_warning = $warning1 !== null ? $warning1 : $warning2;
		}
	}
}

function handleUpload($archive) {
	global $is_participating;
	UOJSubmission::onUpload($archive, $is_participating);
}

function submissionExtraCheck() {
	if (!submission_frequency_check()) {
		UOJLog::warning(
			'a user exceeds the submission frequency limit! '.
			Auth::id().' at problem #'.UOJProblem::info('id')
		);
		return '交题交得太快啦，坐下来喝杯阿华田休息下吧？';
	}
	return '';
}

function setupSubmission() {
	global $is_participating, $forms, $problem_presentation;
	$pre = $problem_presentation['submission'];
	$succ_href = $is_participating ? '/contest/'.UOJContest::info('id').'/submissions' : '/submissions';

	if ($pre['format']['zip']) {
		$zip_answer_form = new UOJZipSubmissionForm(
			'zip-answer',
			$pre['requirement'],
			'FS::randomAvailableSubmissionFileName',
			'handleUpload'
		);
		$zip_answer_form->extra_validators[] = 'submissionExtraCheck';
		$zip_answer_form->succ_href = $succ_href;
		$zip_answer_form->runAtServer();
		$forms['zip_answer'] = $zip_answer_form;
	}

	if ($pre['format']['normal']) {
		$answer_form = new UOJNormalSubmissionForm(
			'answer',
			$pre['requirement'],
			'FS::randomAvailableSubmissionFileName',
			'handleUpload'
		);
		$answer_form->extra_validator = 'submissionExtraCheck';
		$answer_form->succ_href = $succ_href;
		$answer_form->runAtServer();
		$forms['answer'] = $answer_form;
	}
}

function setupQuiz($enabled) {
	global $is_participating, $forms, $problem_presentation;
	$succ_href = $is_participating ? '/contest/'.UOJContest::info('id').'/submissions' : '/submissions';

	$quiz_form = new UOJQuizSubmissionForm(
		'quiz',
		$problem_presentation['quiz'],
		'FS::randomAvailableSubmissionFileName',
		'handleUpload'
	);
	$quiz_form->extra_validator = 'submissionExtraCheck';
	$quiz_form->succ_href = $succ_href;
	$quiz_form->no_submit = !$enabled;
	$quiz_form->runAtServer();
	$forms['quiz'] = $quiz_form;
}

function handleCustomTestUpload($archive) {
	UOJCustomTestSubmission::onUpload($archive);
}

function setupCustomTest() {
	global $forms, $problem_presentation;
	$pre = $problem_presentation['custom_test'];

	UOJCustomTestSubmission::init(UOJProblem::cur(), Auth::user());

	if (UOJRequest::get('get') == 'custom-test-status-details') {
		if (!UOJCustomTestSubmission::cur()) {
			echo json_encode(null);
		} else if (!UOJCustomTestSubmission::cur()->hasJudged()) {
			echo json_encode([
				'judged' => false,
				'waiting' => true,
				'html' => UOJCustomTestSubmission::cur()->getStatusDetailsHTML(),
			]);
		} else {
			ob_start();
			$styler = new CustomTestSubmissionDetailsStyler();
			if (!UOJCustomTestSubmission::cur()->userPermissionCodeCheck(Auth::user(), UOJProblem::cur()->getExtraConfig('view_details_type'))) {
				$styler->fade_all_details = true;
			}
			echoJudgmentDetails(UOJCustomTestSubmission::cur()->getResult('details'), $styler, 'custom_test_details');
			$result = ob_get_contents();
			ob_end_clean();
			echo json_encode([
				'judged' => true,
				'waiting' => false,
				'html' => UOJCustomTestSubmission::cur()->getStatusDetailsHTML(),
				'result' => $result
			]);
		}
		die();
	}

	$custom_test_form = new UOJNormalSubmissionForm(
		'custom_test',
		$pre['requirement'],
		'FS::randomAvailableTmpFileName',
		'handleCustomTestUpload'
	);
	$custom_test_form->appendHTML('<div id="div-custom_test_result"></div>');
	$custom_test_form->succ_href = 'none';
	$custom_test_form->extra_validator = function() {
		if (UOJCustomTestSubmission::cur() && !UOJCustomTestSubmission::cur()->hasJudged()) {
			return '上一个测评尚未结束';
		}
		return '';
	};
	$custom_test_form->ctrl_enter_submit = true;
	$custom_test_form->prevent_multiple_submit = false;
	$custom_test_form->setAjaxSubmit(<<<EOD
		function(response_text) {
			custom_test_onsubmit(
				response_text,
				$('#div-custom_test_result')[0],
				'{$_SERVER['REQUEST_URI']}?get=custom-test-status-details'
			)
		}
		EOD
	);
	$custom_test_form->submit_button_config['text'] = UOJLocale::get('problems::run');
	$custom_test_form->runAtServer();

	$forms['custom_test'] = $custom_test_form;
}

$tabs = [
	'statement' => [
		'name' => '<span class="glyphicon glyphicon-book"></span> '.UOJLocale::get('problems::statement'),
	]
];

$forms = [];

$submission_error = null;
if ($pre_submit_check_ret !== true) {
	$submission_error = $pre_submit_check_ret;
} elseif ($no_more_submission) {
	$submission_error = $no_more_submission;
}

if ($problem_presentation['mode'] == 'normal') {
	$tabs += [
		'submit-answer' => [
			'name' => '<span class="glyphicon glyphicon-upload"></span> '.UOJLocale::get('problems::submit'),
		]
	];

	if (!$submission_error) {
		setupSubmission();
	}

	$custom_test_enabled = $problem_presentation['custom_test']['requirement'] && $pre_submit_check_ret === true;
	if ($custom_test_enabled) {
		setupCustomTest();

		$tabs += [
			'custom-test' => [
				'name' => '<span class="glyphicon glyphicon-console"></span> '.UOJLocale::get('problems::custom test'),
			]
		];
	}
} else {
	setupQuiz(!$submission_error);
}

if (UOJProblem::cur()->userCanManage(Auth::user())) {
	$tabs += [
		'manage' => [
			'name' => UOJLocale::get('problems::manage'),
			'url' => "/problem/{$problem['id']}/manage/statement"
		]
	];
}
if (UOJContest::cur()) {
	$tabs += [
		'back-to-contest' => [
			'name' => UOJLocale::get('contests::back to the contest'),
			'url' => "/contest/".UOJContest::info('id')
		]
	];
}

requireLib('mathjax');
requireLib('shjs');

?>
<?php echoUOJPageHeader(HTML::stripTags($problem['title']) . ' - ' . UOJLocale::get('problems::problem')) ?>
<?php if (!Auth::check() || UOJProblem::cur()->userCanClickZan(Auth::user())): // no login or can zan ?>
	<div class="pull-right">
		<?= UOJProblem::cur()->getZanBlock() ?>
	</div>
<?php endif ?>

<?php if (UOJContest::cur()): ?>
<div class="page-header row">
	<h1 class="col-md-3 text-left"><small><?= UOJContest::info('name') ?></small></h1>
	<h1 class="col-md-6 text-center"><?= UOJProblem::cur()->getTitle(['with' => 'letter', 'simplify' => true]) ?></h1>
	<div class="col-md-3 text-right" style="margin-top: 20px" id="contest-countdown"></div>
</div>
<span class="pull-right">
    <a role="button" class="btn btn-primary" href="<?= UOJProblem::cur()->getAttachmentUri() ?>"><span class="glyphicon glyphicon-download-alt"></span> <?= UOJLocale::get('problems::download attachment') ?></a>
	<?php if (UOJContest::cur()->userCanSeeProblemStatistics(Auth::user())): ?>
		<a role="button" class="btn btn-info" href="/contest/<?= UOJContest::info('id') ?>/problem/<?= $problem['id'] ?>/statistics"><span class="glyphicon glyphicon-stats"></span> <?= UOJLocale::get('problems::statistics') ?></a>
	<?php endif ?>
</span>
<?php if (UOJContest::cur()->progress() <= CONTEST_IN_PROGRESS): ?>
<script type="text/javascript">
$('#contest-countdown').countdown(<?= UOJContest::info('end_time')->getTimestamp() - UOJTime::$time_now->getTimestamp() ?>);
</script>
<?php endif ?>
<?php else: ?>
<h1 class="page-header text-center">#<?= $problem['id']?>. <?= $problem['title'] ?></h1>
<span class="pull-right">
    <a role="button" class="btn btn-primary" href="<?= UOJProblem::cur()->getAttachmentUri() ?>"><span class="glyphicon glyphicon-download-alt"></span> <?= UOJLocale::get('problems::download attachment') ?></a>
	<a role="button" class="btn btn-info" href="/problem/<?= $problem['id'] ?>/statistics"><span class="glyphicon glyphicon-stats"></span> <?= UOJLocale::get('problems::statistics') ?></a>
</span>
<?php endif ?>
<div class="visible-xs-block visible-sm-block" style="margin: 70px"></div>

<?= HTML::samepage_tablist('tab', $tabs, 'statement') ?>

<?php
	uojIncludeView("problem-content-{$problem_presentation['mode']}-mode", [
		'problem_presentation' => $problem_presentation,
		'forms' => $forms,
		'submission_error' => $submission_error,
		'submission_warning' => $submission_warning,
	]);
?>

<?php echoUOJPageFooter() ?>
