<?php
	requirePHPLib('form');
	
	if (!validateUInt($_GET['id']) || !($hack = queryHack($_GET['id']))) {
		become404Page();
	}
	$submission = querySubmission($hack['submission_id']);	
	$problem = queryProblemBrief($submission['problem_id']);
	$problem_extra_config = getProblemExtraConfig($problem);

	if ($submission['contest_id']) {
		$contest = queryContest($submission['contest_id']);
		genMoreContestInfo($contest);
	} else {
		$contest = null;
	}

	if (!isHackVisibleToUser($hack, $problem, $myUser)) {
		become403Page();
	}
	
	if (isSuperUser($myUser)) {
		$delete_form = new UOJForm('delete');
		$delete_form->handle = function() {
			global $hack;
			mysql_query("delete from hacks where id = {$hack['id']}");
		};
		$delete_form->submit_button_config['class_str'] = 'btn btn-danger';
		$delete_form->submit_button_config['text'] = '删除此Hack';
		$delete_form->submit_button_config['align'] = 'right';
		$delete_form->submit_button_config['smart_confirm'] = '';
		$delete_form->succ_href = "/hacks";
		$delete_form->runAtServer();
	}
	
	$should_show_content = hasViewPermission($problem_extra_config['view_content_type'], $myUser, $problem, $submission);
	$should_show_all_details = hasViewPermission($problem_extra_config['view_all_details_type'], $myUser, $problem, $submission);
	$should_show_details = hasViewPermission($problem_extra_config['view_details_type'], $myUser, $problem, $submission);
	$should_show_details_to_me = isSuperUser($myUser);
	if ($hack['success'] === null) {
		$should_show_all_details = false;
	}
	if (!isSubmissionFullVisibleToUser($submission, $contest, $problem, $myUser)
		|| !isHackFullVisibleToUser($hack, $contest, $problem, $myUser)) {
		$should_show_content = $should_show_all_details = false;
	}
	
	if ($should_show_all_details) {
		$styler = new HackDetailsStyler();
		if (!$should_show_details) {
			$styler->fade_all_details = true;
			$styler->show_small_tip = false;
		}
	}
?>
<?php
	$REQUIRE_LIB['shjs'] = "";
?>
<?php echoUOJPageHeader(UOJLocale::get('problems::hack').' #'.$hack['id']) ?>

<?php echoHackListOnlyOne($hack, array(), $myUser) ?>
<?php if ($should_show_all_details): ?>
	<div class="panel panel-info">
		<div class="panel-heading">
			<h4 class="panel-title"><?= UOJLocale::get('details') ?></h4>
		</div>
		<div class="panel-body">
			<?php echoJudgementDetails($hack['details'], $styler, 'details') ?>
			<?php if ($should_show_details_to_me): ?>
				<?php if ($styler->fade_all_details): ?>
					<hr />
					<?php echoHackDetails($hack['details'], 'final_details') ?>
				<?php endif ?>
			<?php endif ?>
		</div>
	</div>
<?php endif ?>
<?php echoSubmissionsListOnlyOne($submission, array(), $myUser) ?>
<?php if ($should_show_content): ?>
	<?php echoSubmissionContent($submission, getProblemSubmissionRequirement($problem)) ?>
<?php endif ?>

<?php if (isset($delete_form)): ?>
	<?php $delete_form->printHTML() ?>
<?php endif ?>
<?php echoUOJPageFooter() ?>
