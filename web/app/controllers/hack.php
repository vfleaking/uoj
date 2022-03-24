<?php
    requirePHPLib('form');
    requirePHPLib('judger');
    requirePHPLib('svn');
    
    UOJHack::init(UOJRequest::get('id')) || UOJResponse::page404();
    UOJHack::cur()->setProblem() || UOJResponse::page404();
    UOJHack::cur()->userCanView(Auth::user(), ['ensure' => true]);

    if (UOJHack::cur()->setSubmission()) {
        UOJHack::cur()->submission->setAsCur();
        UOJSubmission::cur()->setProblem(['problem' => UOJHack::cur()->problem]) || UOJResponse::page404();
        UOJSubmission::cur()->userCanView(Auth::user(), ['ensure' => true]);
    }

	if (UOJHack::cur()->userCanDelete(Auth::user())) {
		$delete_form = new UOJForm('delete');
		$delete_form->handle = function() {
			DB::delete([
                "delete from hacks",
                "where", ["id" => UOJHack::info('id')]
            ]);
		};
		$delete_form->submit_button_config['class_str'] = 'btn btn-danger';
		$delete_form->submit_button_config['text'] = '删除此Hack';
		$delete_form->submit_button_config['align'] = 'right';
		$delete_form->submit_button_config['smart_confirm'] = '';
		$delete_form->succ_href = "/hacks";
		$delete_form->runAtServer();
    }
    
    if (UOJHack::cur()->userCanReview(Auth::user())) {
		$addex_form = new UOJForm('addex');
		$addex_form->handle = function() {
            $input = UOJContext::storagePath().UOJHack::info('input');
            $new_in = "{$input}_in";
            $new_out = "{$input}_out";
            $reason = null;
            $err = svnAddHackPoint(UOJHack::cur()->problem->info, $new_in, $new_out, $reason, Auth::user());
            $err === '' || UOJResponse::message($err);
            unlink($new_in);
            unlink($new_out);
            DB::update([
                "update hacks",
                "set", [
                    'status' => 'Judged',
                ], "where", ['id' => UOJHack::info('id')]
            ]);
		};
		$addex_form->submit_button_config['class_str'] = 'btn btn-danger';
		$addex_form->submit_button_config['text'] = '确认无误，添加到测试数据';
		$addex_form->submit_button_config['align'] = 'right';
		$addex_form->submit_button_config['smart_confirm'] = '';
		$addex_form->succ_href = "/hacks";
		$addex_form->runAtServer();
    }

    $perm = UOJHack::cur()->viewerCanSeeComponents(Auth::user());
?>
<?php
	$REQUIRE_LIB['shjs'] = "";
?>
<?php echoUOJPageHeader(UOJLocale::get('problems::hack').' #'.UOJHack::info('id')) ?>

<?php echoHackListOnlyOne(UOJHack::info(), [], Auth::user()) ?>
<?php if (UOJHack::cur()->hasJudged()): ?>
	<?php if ($perm['high_level_details']): ?>
        <div class="panel panel-info">
            <div class="panel-heading">
                <h4 class="panel-title"><?= UOJLocale::get('details') ?></h4>
            </div>
            <div class="panel-body">
                <?php 
                    $styler = new HackDetailsStyler();
                    if (!$perm['low_level_details']) {
                        $styler->fade_all_details = true;
                        $styler->show_small_tip = false;
                    }
                    echoJudgmentDetails(UOJHack::info('details'), $styler, 'details');
                ?>
				<?php if ($perm['manager_view'] && !$perm['low_level_details']): ?>
					<hr />
				    <h4 class="text-info">全部详细信息（仅管理员可见）</h4>
					<?php echoHackDetails(UOJHack::info('details'), 'all_details') ?>
				<?php endif ?>
            </div>
        </div>
    <?php endif ?>
<?php endif ?>

<?php if (UOJSubmission::cur()): ?>
    <?php UOJSubmission::cur()->echoStatusTable(['show_actual_score' => $perm['score']], Auth::user()) ?>
    <?php if ($perm['content'] || $perm['manager_view']): ?>
        <?php UOJSubmission::cur()->echoContent() ?>
    <?php endif ?>
<?php else: ?>
    <h3 class="text-danger">提交记录信息损坏</h3>
<?php endif ?>

<?php if (isset($delete_form)): ?>
	<?php $delete_form->printHTML() ?>
<?php endif ?>

<?php if (isset($addex_form)): ?>
	<div class="top-buffer-sm">
       <?php $addex_form->printHTML() ?>
    </div>
<?php endif ?>

<?php echoUOJPageFooter() ?>
