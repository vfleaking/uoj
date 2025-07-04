<?php

requirePHPLib('judger');

UOJSubmission::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJSubmission::initProblemAndContest() || UOJResponse::page404();
UOJSubmission::cur()->userCanView(Auth::user(), ['ensure' => true]);

$perm = UOJSubmission::cur()->viewerCanSeeComponents(Auth::user());

$can_see_minor = false;
if ($perm['score']) {
    $can_see_minor = UOJSubmission::cur()->userCanSeeMinorVersions(Auth::user());
    UOJSubmissionHistory::init(UOJSubmission::cur(), ['minor' => $can_see_minor]) || UOJResponse::page404();
    if (isset($_GET['time'])) {
        $history_time = UOJRequest::get('time', 'is_short_string');
        !empty($history_time) || UOJResponse::page404();
        UOJSubmission::cur()->loadHistoryByTime($history_time) || UOJResponse::page404();
        UOJSubmission::cur()->isMajor() || UOJResponse::page404();
    } elseif (isset($_GET['tid'])) {
        $can_see_minor || UOJResponse::page404();
        UOJSubmission::cur()->loadHistoryByTID(UOJRequest::get('tid', 'validateUInt')) || UOJResponse::page404();
        !UOJSubmission::cur()->isMajor() || UOJResponse::page404();
    }
}

$submission = UOJSubmission::info();
$submission_result = UOJSubmission::cur()->getResult();
$problem = UOJProblem::info();

if ($can_see_minor) {
    $minor_rejudge_form = new UOJForm('minorrejudge');
    $minor_rejudge_form->handle = function() {
        UOJSubmission::cur()->rejudge([
            'reason_text' => '管理员偷偷重测该提交记录',
            'major' => false
        ]);
        $tid = DB::insert_id();
        redirectTo(UOJSubmission::cur()->getUriForNewTID($tid));
    };
    $minor_rejudge_form->submit_button_config['class_str'] = 'btn btn-primary';
    $minor_rejudge_form->submit_button_config['text'] = '偷偷重新测试';
    $minor_rejudge_form->submit_button_config['align'] = 'right';
    $minor_rejudge_form->runAtServer();
}

if (UOJSubmission::cur()->isLatest()) {
    if (UOJSubmission::cur()->preHackCheck()) {
        $hack_form = new UOJForm('hack');
        
        $hack_form->addTextFileInput('input', '输入数据');
        $hack_form->addCheckBox('use_formatter', '帮我整理文末回车、行末空格、换行符', true);
        $hack_form->handle = function(&$vdata) {
            global $problem, $submission;
            Auth::check() || redirectToLogin();
            
            if ($_POST["input_upload_type"] == 'file') {
                $tmp_name = UOJForm::uploadedFileTmpName("input_file");
                if ($tmp_name == null) {
                    UOJResponse::message('你在干啥……怎么什么都没交过来……？');
                }
            }
            
            $fileName = FS::randomAvailableTmpFileName();
            $fileFullName = UOJContext::storagePath().$fileName;
            if ($_POST["input_upload_type"] == 'editor') {
                file_put_contents($fileFullName, $_POST['input_editor']);
            } else {
                move_uploaded_file($_FILES["input_file"]['tmp_name'], $fileFullName);
            }
            $input_type = isset($_POST['use_formatter']) ? "USE_FORMATTER" : "DONT_USE_FORMATTER";
            DB::insert([
                "insert into hacks",
                "(problem_id, submission_id, hacker, owner, input, input_type, submit_time, status, details, is_hidden)",
                "values", DB::tuple([
                    $problem['id'], $submission['id'], Auth::id(), $submission['submitter'],
                    $fileName, $input_type, DB::now(), 'Waiting', '', $problem['is_hidden']
                ])
            ]);
        };
        $hack_form->max_post_size = 25 * 1024 * 1024;
        $hack_form->max_file_size_mb = 20;
        $hack_form->succ_href = "/hacks";
        
        $hack_form->runAtServer();
    }

    if (UOJSubmission::cur()->userCanRejudge(Auth::user())) {
        $rejudge_form = new UOJForm('rejudge');
        $rejudge_form->handle = function() {
            UOJSubmission::cur()->rejudge();
        };
        $rejudge_form->submit_button_config['class_str'] = 'btn btn-primary';
        $rejudge_form->submit_button_config['text'] = '重新测试';
        $rejudge_form->submit_button_config['align'] = 'right';
        $rejudge_form->runAtServer();
    }
    
    if (UOJSubmission::cur()->userCanDelete(Auth::user())) {
        $delete_form = new UOJForm('delete');
        $delete_form->handle = function() {
            UOJSubmission::cur()->delete();
        };
        $delete_form->submit_button_config['class_str'] = 'btn btn-danger';
        $delete_form->submit_button_config['text'] = '删除此提交记录';
        $delete_form->submit_button_config['align'] = 'right';
        $delete_form->submit_button_config['smart_confirm'] = '';
        $delete_form->succ_href = "/submissions";
        $delete_form->runAtServer();
    }
} else {
    if (UOJSubmission::cur()->userCanDelete(Auth::user()) && !UOJSubmission::cur()->isMajor()) {
        $delete_form = new UOJForm('delete');
        $delete_form->handle = function() {
            UOJSubmission::cur()->deleteThisMinorVersion();
        };
        $delete_form->submit_button_config['class_str'] = 'btn btn-danger';
        $delete_form->submit_button_config['text'] = '删除当前历史记录（保留其他历史记录）';
        $delete_form->submit_button_config['align'] = 'right';
        $delete_form->submit_button_config['smart_confirm'] = '';
        $delete_form->succ_href = UOJSubmission::cur()->getUriForLatest();
        $delete_form->runAtServer();
    }
}

requireLib('shjs');

?>
<?php echoUOJPageHeader(UOJLocale::get('problems::submission').' #'.$submission['id']) ?>
<?php UOJSubmission::cur()->echoStatusTable(['show_actual_score' => $perm['score']], Auth::user()) ?>

<?php
	if ($perm['score']) {
		HTML::echoPanel('panel-info', '测评历史', function() {
			UOJSubmissionHistory::cur()->echoTimeline();
		});
	}
?>

<?php
    if ($perm['manager_view']) {
        HTML::echoPanel('panel-info', '测评机信息（管理员可见）', function() {
            if (empty(UOJSubmission::info('judger'))) {
                echo '暂无';
            } else {
                $judger = DB::selectFirst([
                    "select * from judger_info",
                    "where", [
                        "judger_name" => UOJSubmission::info('judger')
                    ]
                ]);
                if (!$judger) {
                    echo '测评机信息损坏';
                } else {
                    echo '<strong>', $judger['display_name'], '：</strong>', $judger['description'];
                }
            }
        });
    }
?>

<?php if ($perm['content'] || $perm['manager_view']): ?>
    <?php UOJSubmission::cur()->echoContent() ?>
	<?php if (isset($hack_form)): ?>
		<p class="text-center">
			这程序好像有点Bug，我给组数据试试？ <button id="button-display-hack" type="button" class="btn btn-danger btn-xs">Hack!</button>
		</p>
		<div id="div-form-hack" style="display:none" class="bot-buffer-md">
			<p class="text-center text-danger">
				Hack 功能是给大家互相查错用的。请勿故意提交错误代码，然后自己 Hack 自己、贼喊捉贼哦（故意贼喊捉贼会予以封禁处理）
			</p>
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

<?php
    if (UOJSubmission::cur()->hasJudged()) {
	    if ($perm['high_level_details']) {
            HTML::echoPanel('panel-info', UOJLocale::get('details'), function() use($perm, $submission_result) {
                $styler = new SubmissionDetailsStyler();
                if (!$perm['low_level_details']) {
                    $styler->fade_all_details = true;
                    $styler->show_small_tip = false;
                }
                echoJudgmentDetails($submission_result['details'], $styler, 'details');

				if ($perm['manager_view'] && !$perm['low_level_details']) {
                    echo '<hr />';
				    echo '<h4 class="text-info">全部详细信息（管理员可见）</h4>';
					echoSubmissionDetails($submission_result['details'], 'all_details');
                }
            });
        } else if ($perm['manager_view']) {
            HTML::echoPanel('panel-info', '详细（管理员可见）', function() use($submission_result) {
				echoSubmissionDetails($submission_result['details'], 'details');
            });
        }
        if ($perm['manager_view'] && isset($submission_result['final_result'])) {
            HTML::echoPanel('panel-info', '终测结果预测（管理员可见）', function() use($submission_result) {
				echoSubmissionDetails($submission_result['final_result']['details'], 'final_details');
            });
        }
    }
?>

<?php if (isset($minor_rejudge_form)): ?>
	<div class="top-buffer-sm">
        <?php $minor_rejudge_form->printHTML() ?>
	</div>
<?php endif ?>

<?php if (isset($rejudge_form)): ?>
	<div class="top-buffer-sm">
        <?php $rejudge_form->printHTML() ?>
	</div>
<?php endif ?>

<?php if (isset($delete_form)): ?>
	<div class="top-buffer-sm">
		<?php $delete_form->printHTML() ?>
	</div>
<?php endif ?>
<?php echoUOJPageFooter() ?>
