<?php
	requirePHPLib('form');
	requirePHPLib('judger');
	
	if (!validateUInt($_GET['id']) || !($submission = querySubmission($_GET['id']))) {
		become404Page();
	}
	$submission_result = json_decode($submission['result'], true);
	
	$problem = queryProblemBrief($submission['problem_id']);
	$problem_extra_config = getProblemExtraConfig($problem);
	
	if ($submission['contest_id']) {
		$contest = queryContest($submission['contest_id']);
		genMoreContestInfo($contest);
	} else {
		$contest = null;
	}
	if (!isSubmissionVisibleToUser($submission, $problem, $myUser)) {
		become403Page();
	}
	
	$out_status = explode(', ', $submission['status'])[0];
	
	if ($_GET['get'] == 'status-details' && Auth::check() && $submission['submitter'] === Auth::id()) {
		echo json_encode(array(
			'judged' => $out_status == 'Judged',
			'html' => getSubmissionStatusDetails($submission)
		));
		die();
	}
	
	$hackable = $submission['score'] == 100 && $problem['hackable'] == 1;
	if ($hackable) {
		$hack_form = new UOJForm('hack');	
		
		$hack_form->addTextFileInput('input', '输入数据');
		$hack_form->addCheckBox('use_formatter', '帮我整理文末回车、行末空格、换行符', true);
		$hack_form->handle = function(&$vdata) {
			global $myUser, $problem, $submission;
			if ($myUser == null) {
				redirectToLogin();
			}
			
			if ($_POST["input_upload_type"] == 'file') {
				$tmp_name = UOJForm::uploadedFileTmpName("input_file");
				if ($tmp_name == null) {
					becomeMsgPage('你在干啥……怎么什么都没交过来……？');
				}
			}
			
			$fileName = uojRandAvaiableTmpFileName();
			$fileFullName = UOJContext::storagePath().$fileName;
			if ($_POST["input_upload_type"] == 'editor') {
				file_put_contents($fileFullName, $_POST['input_editor']);
			} else {
				move_uploaded_file($_FILES["input_file"]['tmp_name'], $fileFullName);
			}
			$input_type = isset($_POST['use_formatter']) ? "USE_FORMATTER" : "DONT_USE_FORMATTER";
			DB::insert("insert into hacks (problem_id, submission_id, hacker, owner, input, input_type, submit_time, details, is_hidden) values ({$problem['id']}, {$submission['id']}, '{$myUser['username']}', '{$submission['submitter']}', '$fileName', '$input_type', now(), '', {$problem['is_hidden']})");
		};
		$hack_form->succ_href = "/hacks";
		
		$hack_form->runAtServer();
	}

	if ($submission['status'] == 'Judged' && hasProblemPermission($myUser, $problem)) {
		$rejudge_form = new UOJForm('rejudge');
		$rejudge_form->handle = function() {
			global $submission;
			rejudgeSubmission($submission);
		};
		$rejudge_form->submit_button_config['class_str'] = 'btn btn-primary';
		$rejudge_form->submit_button_config['text'] = '重新测试';
		$rejudge_form->submit_button_config['align'] = 'right';
		$rejudge_form->runAtServer();
	}
	
	if (isSuperUser($myUser)) {
		$delete_form = new UOJForm('delete');
		$delete_form->handle = function() {
			global $submission;
			$content = json_decode($submission['content'], true);
			unlink(UOJContext::storagePath().$content['file_name']);
			DB::delete("delete from submissions where id = {$submission['id']}");
			updateBestACSubmissions($submission['submitter'], $submission['problem_id']);
		};
		$delete_form->submit_button_config['class_str'] = 'btn btn-danger';
		$delete_form->submit_button_config['text'] = '删除此提交记录';
		$delete_form->submit_button_config['align'] = 'right';
		$delete_form->submit_button_config['smart_confirm'] = '';
		$delete_form->succ_href = "/submissions";
		$delete_form->runAtServer();
	}
	
	$should_show_content = hasViewPermission($problem_extra_config['view_content_type'], $myUser, $problem, $submission);
	$should_show_all_details = hasViewPermission($problem_extra_config['view_all_details_type'], $myUser, $problem, $submission);
	$should_show_details = hasViewPermission($problem_extra_config['view_details_type'], $myUser, $problem, $submission);
	$should_show_details_to_me = isSuperUser($myUser);
	if (explode(', ', $submission['status'])[0] != 'Judged') {
		$should_show_all_details = false;
	}
	if ($contest != null && $contest['cur_progress'] == CONTEST_IN_PROGRESS) {
		if ($contest['extra_config']["problem_{$submission['problem_id']}"] === 'no-details') {
			$should_show_details = false;
		}
	}
	if (!isSubmissionFullVisibleToUser($submission, $contest, $problem, $myUser)) {
		$should_show_content = $should_show_all_details = false;
	}
	if ($contest != null && hasContestPermission($myUser, $contest)) {
		$should_show_details_to_me = true;
		$should_show_content = true;
		$should_show_all_details = true;
	}
	
	if ($should_show_all_details) {
		$styler = new SubmissionDetailsStyler();
		if (!$should_show_details) {
			$styler->fade_all_details = true;
			$styler->show_small_tip = false;
		}
	}
?>
<?php 
	$REQUIRE_LIB['shjs'] = "";
?>
<?php echoUOJPageHeader(UOJLocale::get('problems::submission').' #'.$submission['id']) ?>
<?php echoSubmissionsListOnlyOne($submission, array(), $myUser) ?>

<?php if ($should_show_content): ?>
	<?php echoSubmissionContent($submission, getProblemSubmissionRequirement($problem)) ?>
	<?php if ($hackable): ?>
		<p class="text-center">
			这程序好像有点Bug，我给组数据试试？ <button id="button-display-hack" type="button" class="btn btn-danger btn-xs">Hack!</button>
		</p>
		<div id="div-form-hack" style="display:none" class="bot-buffer-md">
			<?php $hack_form->printHTML() ?>
		</div>
		<script type="text/javascript">
			$(document).ready(function() {
				$('#button-display-hack').click(function() {
					$('#div-form-hack').toggle('fast');
				});
			});
		</script>
	<?php endif ?>
<?php endif ?>

<?php if ($should_show_all_details): ?>
	<div class="panel panel-info">
		<div class="panel-heading">
			<h4 class="panel-title"><?= UOJLocale::get('details') ?></h4>
		</div>
		<div class="panel-body">
			<?php echoJudgementDetails($submission_result['details'], $styler, 'details') ?>
			<?php if ($should_show_details_to_me): ?>
				<?php if (isset($submission_result['final_result'])): ?>
					<hr />
					<?php echoSubmissionDetails($submission_result['final_result']['details'], 'final_details') ?>
				<?php endif ?>
				<?php if ($styler->fade_all_details): ?>
					<hr />
					<?php echoSubmissionDetails($submission_result['details'], 'final_details') ?>
				<?php endif ?>
			<?php endif ?>
		</div>
	</div>
<?php endif ?>

<?php if (isset($rejudge_form)): ?>
	<?php $rejudge_form->printHTML() ?>
<?php endif ?>

<?php if (isset($delete_form)): ?>
	<div class="top-buffer-sm">
		<?php $delete_form->printHTML() ?>
	</div>
<?php endif ?>
<?php echoUOJPageFooter() ?>
