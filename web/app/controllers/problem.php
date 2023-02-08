<?php

requirePHPLib('form');
requirePHPLib('judger');

UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();

$problem = UOJProblem::cur()->info;
$problem_content = UOJProblem::cur()->queryContent();

if (UOJRequest::get('contest_id')) {
	UOJContest::init(UOJRequest::get('contest_id')) || UOJResponse::page404();
	UOJProblem::upgradeToContestProblem() || UOJResponse::page404();
}
UOJProblem::cur()->userCanView(Auth::user(), ['ensure' => true]);

$pre_submit_check_ret = UOJProblem::cur()->preSubmitCheck();

$is_participating = false;
$no_more_submission = false;
$submission_warning = null;
if (UOJContest::cur() && UOJContest::cur()->userCanParticipateNow(Auth::user())) {
	if (!UOJContest::cur()->userHasMarkedParticipated(Auth::user())) {
		if (UOJContest::cur()->registrantNeedToConfirmParticipation(Auth::user())) {
			redirectTo(UOJContest::cur()->getUri("/confirm-participation?problem_id={$problem['id']}"));
		}
		UOJContest::cur()->markUserAsParticipated(Auth::user());
	}
	$is_participating = true;
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

$submission_requirement = UOJProblem::cur()->getSubmissionRequirement();
$custom_test_requirement = UOJProblem::cur()->getCustomTestRequirement();
$custom_test_enabled = $custom_test_requirement && $pre_submit_check_ret === true;

function handleUpload($zip_file_name, $content, $tot_size) {
	global $is_participating;
	UOJSubmission::onUpload($zip_file_name, $content, $tot_size, $is_participating);
}
function handleCustomTestUpload($zip_file_name, $content, $tot_size) {
	UOJCustomTestSubmission::onUpload($zip_file_name, $content, $tot_size);
}
if ($custom_test_enabled) {
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

	$custom_test_form = newSubmissionForm('custom_test',
		$custom_test_requirement,
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
}

if ($pre_submit_check_ret === true && !$no_more_submission) {
	$submission_extra_validator = function() {
		if (!submission_frequency_check()) {
			UOJLog::warning('a user exceeds the submission frequency limit! '.Auth::id(). ' at problem #'.UOJProblem::info('id'));
			return '交题交得太快啦，坐下来喝杯阿华田休息下吧？';
		}
		return '';
	};
	
	if (UOJProblem::cur()->userCanUploadSubmissionViaZip(Auth::user())) {
		$zip_answer_form = newZipSubmissionForm('zip-answer',
			$submission_requirement,
			'FS::randomAvailableSubmissionFileName',
			'handleUpload'
		);
		$zip_answer_form->extra_validators[] = $submission_extra_validator;
		$zip_answer_form->succ_href = $is_participating ? '/contest/'.UOJContest::info('id').'/submissions' : '/submissions';
		$zip_answer_form->runAtServer();
	}
	
	$answer_form = newSubmissionForm('answer',
		$submission_requirement,
		'FS::randomAvailableSubmissionFileName',
		'handleUpload'
	);
	$answer_form->extra_validator = $submission_extra_validator;
	$answer_form->succ_href = $is_participating ? '/contest/'.UOJContest::info('id').'/submissions' : '/submissions';
	$answer_form->runAtServer();
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

<ul class="nav nav-tabs" role="tablist">
	<li class="active"><a href="#tab-statement" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-book"></span> <?= UOJLocale::get('problems::statement') ?></a></li>
	<li><a href="#tab-submit-answer" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-upload"></span> <?= UOJLocale::get('problems::submit') ?></a></li>
	<?php if ($custom_test_enabled): ?>
	<li><a href="#tab-custom-test" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-console"></span> <?= UOJLocale::get('problems::custom test') ?></a></li>
	<?php endif ?>
	<?php if (UOJProblem::cur()->userCanManage(Auth::user())): ?>
	<li><a href="/problem/<?= $problem['id'] ?>/manage/statement" role="tab"><?= UOJLocale::get('problems::manage') ?></a></li>
	<?php endif ?>
	<?php if (UOJContest::cur()): ?>
	<li><a href="/contest/<?= UOJContest::info('id') ?>" role="tab"><?= UOJLocale::get('contests::back to the contest') ?></a></li>
	<?php endif ?>
</ul>
<div class="tab-content">
	<div class="tab-pane active" id="tab-statement">
		<article class="uoj-article top-buffer-md"><?= $problem_content['statement'] ?></article>
	</div>
	<div class="tab-pane" id="tab-submit-answer">
		<div class="top-buffer-sm"></div>
		<?php if ($pre_submit_check_ret !== true): ?>
		    <h3 class="text-warning"><?= $pre_submit_check_ret ?></h3>
		<?php elseif ($no_more_submission): ?>
		    <h3 class="text-warning"><?= $no_more_submission ?></h3>
		<?php else: ?>
			<?php if ($submission_warning): ?>
				<h3 class="text-warning"><?= $submission_warning ?></h3>
			<?php endif ?>
			<?php if (isset($zip_answer_form)): ?>
                <?php $zip_answer_form->printHTML(); ?>
                <hr />
                <strong><?= UOJLocale::get('problems::or upload files one by one') ?><br /></strong>
			<?php endif ?>
			<?php $answer_form->printHTML(); ?>
		<?php endif ?>
	</div>
	<?php if ($custom_test_enabled): ?>
        <div class="tab-pane" id="tab-custom-test">
            <div class="top-buffer-sm"></div>
            <?php $custom_test_form->printHTML(); ?>
        </div>
	<?php endif ?>
</div>
<?php echoUOJPageFooter() ?>
