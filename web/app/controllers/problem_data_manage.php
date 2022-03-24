<?php

requirePHPLib('form');
requirePHPLib('judger');
requirePHPLib('svn');

UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJProblem::cur()->userCanManage(Auth::user()) || UOJResponse::page403();

$problem = UOJProblem::info();
$problem_extra_config = UOJProblem::cur()->getExtraConfig();

if (isset($_POST['getsvn'])) {
	if (Auth::check()) {
		$html = <<<EOD
		<base target="_blank" />

		<p>尊敬的用户 {$myUser['username']} 您好：</p>
		<p>您的 SVN 账号是：{$myUser['username']}，密码是：{$myUser['svn_password']}</p>
		<p>Universal Online Judge</p>

		<style type="text/css">
		body{font-size:14px;font-family:arial,verdana,sans-serif;line-height:1.666;padding:0;margin:0;overflow:auto;white-space:normal;word-wrap:break-word;min-height:100px}
		pre {white-space:pre-wrap;white-space:-moz-pre-wrap;white-space:-pre-wrap;white-space:-o-pre-wrap;word-wrap:break-word}
		</style>
		EOD;
		
		try {
			$mailer = UOJMail::noreply();
			$mailer->addAddress($myUser['email'], $myUser['username']);
			$mailer->Subject = "svn密码";
			$mailer->msgHTML($html);
			$mailer->send();
			die('good');
		} catch (PHPMailer\PHPMailer\Exception $e) {
			UOJLog::error('PHPMailer: '.$mailer->ErrorInfo);
			die();
		}
	}
}

if (isset($_POST['problem_data_file_submit'])) {
	if ($_FILES["problem_data_file"]["error"] > 0) {
		$errmsg = "Error: ".$_FILES["problem_data_file"]["error"];
		UOJResponse::message('<div>' . HTML::escape($errmsg) . '</div><a href="/problem/'.$problem['id'].'/manage/data">返回</a>');
	}
	else{
		$zip_mime_types = array('application/zip', 'application/x-zip', 'application/x-zip-compressed');
		if(in_array($_FILES["problem_data_file"]["type"], $zip_mime_types)){
			$errmsg = svnUploadViaBrowser($problem, $_FILES["problem_data_file"]["tmp_name"]);
			if ($errmsg !== '') {
				UOJResponse::message('<div>' . $errmsg . '</div><a href="/problem/'.$problem['id'].'/manage/data">返回</a>');
			}

			echo "<script>alert('上传成功！')</script>";
		}else{
			$errmsg = "请上传zip格式！";
			UOJResponse::message('<div>' . $errmsg . '</div><a href="/problem/'.$problem['id'].'/manage/data">返回</a>');
		}
	}
}

$info_form = new UOJForm('info');

if (isSuperUser($myUser)) {
	$info_form->addVInput('submission_requirement', 'text', '修改提交文件配置', $problem['submission_requirement'],
		function ($submission_requirement, &$vdata) {
			$submission_requirement = json_decode($submission_requirement, true);
			if ($submission_requirement === null) {
				return '不是合法的JSON';
			}
			$vdata['submission_requirement'] = json_encode($submission_requirement);
		},
		null);
	$info_form->addVInput('extra_config', 'text', '修改其他配置', $problem['extra_config'],
		function ($extra_config, &$vdata) {
			$extra_config = json_decode($extra_config, true);
			if ($extra_config === null) {
				return '不是合法的JSON';
			}
			$vdata['extra_config'] = json_encode($extra_config);
		},
		null);
	$info_form->handle = function(&$vdata) {
		global $problem;
		DB::update([
			"update problems",
			"set", [
				"submission_requirement" => $vdata['submission_requirement'],
				"extra_config" => $vdata['extra_config']
			], "where", ["id" => $problem['id']]
		]);
	};
} else {
	$info_form->no_submit = true;
}

function echoDataConfigureButton() {
	global $problem;
	echo <<<EOD
	<div class="top-buffer-md text-center">
		<a class="btn btn-primary" href="/problem/{$problem['id']}/manage/data/configure">修改数据配置</a>
	</div>
	EOD;
}
function echoDataConfigureHeader() {
	echo '<h4>3. 数据配置</h4>';
}

function displayProblemConf(UOJProblemDataDisplayer $self) {
	global $info_form;
	uojIncludeView('problem-data-basic-info');
	$info_form->printHTML();
	echoDataConfigureHeader();
	$self->echoProblemConfTable();
	echoDataConfigureButton();
	$self->echoFilePre('problem.conf');
}

function addTestsTab(UOJProblemDataDisplayer $disp, array $problem_conf) {
	$n_tests = getUOJConfVal($problem_conf, 'n_tests', 10);
	if (!validateUInt($n_tests)) {
		$disp->setProblemConfRowStatus('n_tests', 'danger');
		return false;
	}

	$inputs = [];
	$outputs = [];
	for ($num = 1; $num <= $n_tests; $num++) {
		$inputs[$num] = getUOJProblemInputFileName($problem_conf, $num);
		$outputs[$num] = getUOJProblemOutputFileName($problem_conf, $num);
		unset($disp->rest_data_files[$inputs[$num]]);
		unset($disp->rest_data_files[$outputs[$num]]);
	}

	$disp->addTab('tests', function($self) use($inputs, $outputs, $n_tests) {
		for ($num = 1; $num <= $n_tests; $num++) {
			echo '<div class="row">';
			echo '<div class="col-md-6">';
			$self->echoFilePre($inputs[$num]);
			echo '</div>';
			echo '<div class="col-md-6">';
			$self->echoFilePre($outputs[$num]);
			echo '</div>';
			echo '</div>';
		}
	});
	return true;
}

function addExTestsTab(UOJProblemDataDisplayer $disp, array $problem_conf) {
	$has_extra_tests = !(isset($problem_conf['submit_answer']) && $problem_conf['submit_answer'] == 'on');

	if (!$has_extra_tests) {
		return false;
	}

	$n_ex_tests = getUOJConfVal($problem_conf, 'n_ex_tests', 0);
	if (!validateUInt($n_ex_tests)) {
		$disp->setProblemConfRowStatus('n_ex_tests', 'danger');
		return false;
	}

	if ($n_ex_tests == 0) {
		return false;
	}

	$inputs = [];
	$outputs = [];
	for ($num = 1; $num <= $n_ex_tests; $num++) {
		$inputs[$num] = getUOJProblemExtraInputFileName($problem_conf, $num);
		$outputs[$num] = getUOJProblemExtraOutputFileName($problem_conf, $num);
		unset($disp->rest_data_files[$inputs[$num]]);
		unset($disp->rest_data_files[$outputs[$num]]);
	}

	$disp->addTab('extra tests', function($self) use($inputs, $outputs, $n_ex_tests) {
		for ($num = 1; $num <= $n_ex_tests; $num++) {
			echo '<div class="row">';
			echo '<div class="col-md-6">';
			$self->echoFilePre($inputs[$num]);
			echo '</div>';
			echo '<div class="col-md-6">';
			$self->echoFilePre($outputs[$num]);
			echo '</div>';
			echo '</div>';
		}
	});
	return true;
}

function addSrcTab(UOJProblemDataDisplayer $disp, $tab_name, string $name) {
	$src = UOJLang::findSourceCode($name, '', [$disp, 'isFile']);
	if ($src !== false) {
		unset($disp->rest_data_files[$src['path']]);
	}
	unset($disp->rest_data_files[$name]);

	$disp->addTab($tab_name, function($self) use($name, $src) {
		if ($src !== false) {
			$self->echoFilePre($src['path']);
		}
		$self->echoFilePre($name);
	});
	return true;
}

function getDataDisplayer() {
	$disp = new UOJProblemDataDisplayer(UOJProblem::cur());

	$problem_conf = UOJProblem::cur()->getProblemConf();
	if ($problem_conf === -1) {
		return $disp->addTab('problem.conf', function($self) {
			global $info_form;
			uojIncludeView('problem-data-basic-info');
			$info_form->printHTML();
			echoDataConfigureHeader();
			$self->echoFileNotFound('problem.conf');
			echoDataConfigureButton();
		});
	} elseif ($problem_conf === -2) {
		return $disp->addTab('problem.conf', function($self) {
			global $info_form;
			uojIncludeView('problem-data-basic-info');
			$info_form->printHTML();
			echoDataConfigureHeader();
			echo '<h4 class="text-danger">problem.conf 格式有误</h4>';
			$self->echoFilePre('problem.conf');
			echoDataConfigureButton();
		});
	}

	$disp->setProblemConf($problem_conf);
	unset($disp->rest_data_files['problem.conf']);
	unset($disp->rest_data_files['download.zip']);
	$disp->addTab('problem.conf', 'displayProblemConf');
	addTestsTab($disp, $problem_conf);
	addExTestsTab($disp, $problem_conf);

	$judger_name = getUOJConfVal($problem_conf, 'use_builtin_judger', null);
	if ($judger_name === null) {
		return $disp;
	} elseif ($judger_name === 'on') {
		if (!isset($problem_conf['interaction_mode'])) {
			if (isset($problem_conf['use_builtin_checker'])) {
				$disp->addTab('checker', function($self) {
					echo '<h4>use builtin checker : ', $self->problem_conf['use_builtin_checker']['val'], '</h4>';
				});
			} else {
				addSrcTab($disp, 'checker', 'chk');
			}
		}
		if (UOJProblem::info('hackable')) {
			addSrcTab($disp, 'standard', 'std');
			addSrcTab($disp, 'validator', 'val');
		}
		if (isset($problem_conf['interaction_mode'])) {
			addSrcTab($disp, 'interactor', 'interactor');
		}
		return $disp;
	} else {
		return $disp->setProblemConfRowStatus('use_builtin_judger', 'danger');
	}
}

$data_disp = getDataDisplayer();

if (isset($_GET['display_file'])) {
	$data_disp->displayFile(UOJRequest::get('file_name', 'is_string'));
	die();
}

$hackable_form = new UOJForm('hackable');
$hackable_form->handle = function() {
	global $problem;
	$problem['hackable'] = !$problem['hackable'];
	$ret = svnSyncProblemData($problem, Auth::user());
	if ($ret) {
		UOJResponse::message('<div>' . $ret . '</div><a href="/problem/'.$problem['id'].'/manage/data">返回</a>');
	}
	
	$hackable = $problem['hackable'] ? 1 : 0;
	DB::update([
		"update problems",
		"set", ["hackable" => $hackable],
		"where", ["id" => $problem['id']]
	]);
};
$hackable_form->submit_button_config['class_str'] = 'btn btn-warning btn-block';
$hackable_form->submit_button_config['text'] = $problem['hackable'] ? '禁止使用hack' : '允许使用hack';
$hackable_form->submit_button_config['smart_confirm'] = '';

$data_form = new UOJForm('data');
$data_form->handle = function() {
	global $problem;
	set_time_limit(60 * 5);
	$ret = svnSyncProblemData($problem, Auth::user());
	if ($ret) {
		UOJResponse::message('<div>' . $ret . '</div><a href="/problem/'.$problem['id'].'/manage/data">返回</a>');
	}
};
$data_form->submit_button_config['class_str'] = 'btn btn-primary btn-block';
$data_form->submit_button_config['text'] = '与svn仓库同步';
$data_form->submit_button_config['smart_confirm'] = '';

$clear_data_form = new UOJForm('clear_data');
$clear_data_form->handle = function() {
	svnClearProblemData(UOJProblem::info());
};
$clear_data_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
$clear_data_form->submit_button_config['text'] = '清空题目数据';
$clear_data_form->submit_button_config['smart_confirm'] = '';

$rejudge_form = new UOJForm('rejudge');
$rejudge_form->handle = function() {
	UOJSubmission::rejudgeProblem(UOJProblem::cur());
};
$rejudge_form->succ_href = "/submissions?problem_id={$problem['id']}";
$rejudge_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
$rejudge_form->submit_button_config['text'] = '重测该题';
$rejudge_form->submit_button_config['smart_confirm'] = '';

$rejudgege97_form = new UOJForm('rejudgege97');
$rejudgege97_form->handle = function() {
	UOJSubmission::rejudgeProblemGe97(UOJProblem::cur());
};
$rejudgege97_form->succ_href = "/submissions?problem_id={$problem['id']}";
$rejudgege97_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
$rejudgege97_form->submit_button_config['text'] = '重测 >=97 的程序';
$rejudgege97_form->submit_button_config['smart_confirm'] = '';

$view_type_form = new UOJForm('view_type');
$view_type_form->addVSelect('view_content_type', [
		'NONE' => '禁止',
		'SELF' => '仅自己',
		'ALL_AFTER_AC' => 'AC后',
		'ALL' => '所有人'
	],
	'查看提交文件:',
	$problem_extra_config['view_content_type']
);
$view_type_form->addVSelect('view_all_details_type', [
		'NONE' => '禁止',
		'SELF' => '仅自己',
		'ALL_AFTER_AC' => 'AC后',
		'ALL' => '所有人'
	],
	'查看全部详细信息:',
	$problem_extra_config['view_all_details_type']
);
$view_type_form->addVSelect('view_details_type', [
		'NONE' => '禁止',
		'SELF' => '仅自己',
		'ALL_AFTER_AC' => 'AC后',
		'ALL' => '所有人'
	],
	'查看测试点详细信息:',
	$problem_extra_config['view_details_type']
);
$view_type_form->handle = function() {
	global $problem, $problem_extra_config;
	$config = $problem_extra_config;
	$config['view_content_type'] = $_POST['view_content_type'];
	$config['view_all_details_type'] = $_POST['view_all_details_type'];
	$config['view_details_type'] = $_POST['view_details_type'];
	$esc_config = json_encode($config);
	DB::update([
		"update problems",
		"set", ["extra_config" => $esc_config],
		"where", ["id" => $problem['id']]
	]);
};
$view_type_form->submit_button_config['class_str'] = 'btn btn-warning btn-block top-buffer-sm';

if ($problem['hackable']) {
	$test_std_form = new UOJForm('test_std');
	$test_std_form->handle = function() {
		global $problem, $data_disp;
		
		$user_std = UOJUser::query('std');
		if (!$user_std) {
			UOJResponse::message('Please create an user named "std"');
		}
		
		$requirement = json_decode($problem['submission_requirement'], true);

		$src_std = UOJLang::findSourceCode('std', '', [$data_disp, 'isFile']);
		if ($src_std === false) {
			UOJResponse::message('未找到std！');
		}
		
		$zip_file_name = FS::randomAvailableSubmissionFileName();
		$zip_file = new ZipArchive();
		if ($zip_file->open(UOJContext::storagePath().$zip_file_name, ZipArchive::CREATE) !== true) {
			UOJResponse::message('提交失败');
		}
	
		$content = [];
		$content['file_name'] = $zip_file_name;
		$content['config'] = [];
		$tot_size = 0;
		foreach ($requirement as $req) {
			if ($req['type'] == "source code") {
				$content['config'][] = ["{$req['name']}_language", $src_std['lang']];
				if ($zip_file->addFromString($req['file_name'], $data_disp->getFile($src_std['path'])) === false) {
					$zip_file->close();
					unlink(UOJContext::storagePath().$zip_file_name);
					UOJResponse::message('提交失败');
				}
				$tot_size += $zip_file->statName($req['file_name'])['size'];
			}
		}
	
		$zip_file->close();
	
		$content['config'][] = ['validate_input_before_test', 'on'];
		$content['config'][] = ['problem_id', $problem['id']];
		$esc_content = json_encode($content);
		
		$result = [];
		$result['status'] = "Waiting";
		$result_json = json_encode($result);
		$is_hidden = $problem['is_hidden'] ? 1 : 0;
		
		DB::insert([
			"insert into submissions",
			"(problem_id, submit_time, submitter, content, language, tot_size, status, result, is_hidden)",
			"values", DB::tuple([
				$problem['id'], DB::now(), $user_std['username'], $esc_content,
				$src_std['lang'], $tot_size, $result['status'], $result_json, $is_hidden
			])
		]);
	};
	$test_std_form->succ_href = "/submissions?problem_id={$problem['id']}";
	$test_std_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
	$test_std_form->submit_button_config['text'] = '检验数据正确性';
	$test_std_form->runAtServer();
}

$hackable_form->runAtServer();
$view_type_form->runAtServer();
$data_form->runAtServer();
$clear_data_form->runAtServer();
$rejudge_form->runAtServer();
$rejudgege97_form->runAtServer();
$info_form->runAtServer();

requireLib('dialog');

?>
<?php echoUOJPageHeader(HTML::stripTags($problem['title']) . ' - 数据 - 题目管理') ?>
<?php uojIncludeView('problem-manage-header', ['cur_tab' => 'data']) ?>

<div class="row">
	<div class="col-md-10 top-buffer-sm">
		<div class="row">
			<div class="col-md-3 top-buffer-sm" id="div-file_list">
				<ul class="nav nav-pills nav-stacked">
					<?php $data_disp->echoAllTabs('problem.conf'); ?>
				</ul>
			</div>
			<div class="col-md-9 top-buffer-sm" id="div-file_content">
				<?php $data_disp->displayFile('problem.conf'); ?>
			</div>
			<script type="text/javascript">
				curFileName = '';
				$('#div-file_list a').click(function(e) {
					$('#div-file_content').html('<h3>loading...</h3>');
					$(this).tab('show');

					var fileName = $(this).text();
					curFileName = fileName;
					$.get('/problem/<?= $problem['id'] ?>/manage/data', {
							display_file: '',
							file_name: fileName
						},
						function(data) {
							if (curFileName != fileName) {
								return;
							}
							$('#div-file_content').html(data);
						},
						'html'
					);
					return false;
				});
			</script>
		</div>
	</div>
	<div class="col-md-2 top-buffer-sm">
		<div class="top-buffer-md">
			<?php if ($problem['hackable']): ?>
				<span class="glyphicon glyphicon-ok"></span> hack功能已启用
			<?php else: ?>
				<span class="glyphicon glyphicon-remove"></span> hack功能已停用
			<?php endif ?>
			<?php $hackable_form->printHTML() ?>
		</div>
		<div class="top-buffer-md">
			<?php $data_form->printHTML(); ?>
		</div>
		<div class="top-buffer-md">
		<?php if ($problem['hackable']): ?>
			<?php $test_std_form->printHTML() ?>
		<?php endif ?>
		</div>
		<div class="top-buffer-md">
			<button id="button-display_view_type" type="button" class="btn btn-info btn-block" onclick="$('#div-view_type').toggle('fast');">修改提交记录可视权限</button>
			<div class="top-buffer-sm" id="div-view_type" style="display:none; padding-left:5px; padding-right:5px;">
				<?php $view_type_form->printHTML(); ?>
			</div>
		</div>
		<div class="top-buffer-md">
			<?php $clear_data_form->printHTML(); ?>
		</div>
		<div class="top-buffer-md">
			<?php $rejudge_form->printHTML(); ?>
		</div>
		<div class="top-buffer-md">
			<?php $rejudgege97_form->printHTML(); ?>
		</div>
	</div>
	
	<div class="modal fade" id="UploadDataModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  		<div class="modal-dialog">
    			<div class="modal-content">
      				<div class="modal-header">
						<h4 class="modal-title" id="myModalLabel">直接上传数据压缩包</h4>
        				<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
      				</div>
      				<div class="modal-body">
        				<form action="" method="post" enctype="multipart/form-data" role="form">
							<div class="form-group">
									<label for="exampleInputFile">上传zip文件</label>
									<input type="file" name="problem_data_file" id="problem_data_file">
									<p class="help-block">说明：请将所有数据放置于压缩包根目录内。若压缩包内仅存在文件夹而不存在文件，则会将这些一级子文件夹下的内容移动到根目录下，然后这些一级子文件夹删除；若这些子文件夹内存在同名文件，则会发生随机替换，仅保留一个副本。</p>
							</div>
							<input type="hidden" name="problem_data_file_submit" value="submit">
      				</div>
      				<div class="modal-footer">
						<button type="submit" class="btn btn-success">上传</button>
						</form>
        				<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
      				</div>
    			</div>
  		</div>
	</div>

</div>
<?php echoUOJPageFooter() ?>
