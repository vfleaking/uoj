<?php
	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('svn');
	
	if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
		become404Page();
	}
	if (!hasProblemPermission($myUser, $problem)) {
		become403Page();
	}
	
	$problem_extra_config = getProblemExtraConfig($problem);

	if (isset($_POST['getsvn'])) {
		if (Auth::check()) {
			$html = <<<EOD
<base target="_blank" />

<p>{$myUser['username']}您好，</p>
<p>您的svn密码是：{$myUser['svn_password']}</p>
<p>Universal Online Judge</p>

<style type="text/css">
body{font-size:14px;font-family:arial,verdana,sans-serif;line-height:1.666;padding:0;margin:0;overflow:auto;white-space:normal;word-wrap:break-word;min-height:100px}
pre {white-space:pre-wrap;white-space:-moz-pre-wrap;white-space:-pre-wrap;white-space:-o-pre-wrap;word-wrap:break-word}
</style>
EOD;
			
			$mailer = UOJMail::noreply();
			$mailer->addAddress($myUser['email'], $myUser['username']);
			$mailer->Subject = "svn密码";
			$mailer->msgHTML($html);
			if ($mailer->send()) {  
				echo 'good';
			} else {
			    error_log('PHPMailer: '.$mailer->ErrorInfo);
			}
			die();
		}
	}
	
	$data_dir = "/var/uoj_data/${problem['id']}";

	function echoFileNotFound($file_name) {
		echo '<h4>', htmlspecialchars($file_name), '<sub class="text-danger"> ', 'file not found', '</sub></h4>';
	}
	function echoFilePre($file_name) {
		global $data_dir;
		$file_full_name = $data_dir . '/' . $file_name;

		$finfo = finfo_open(FILEINFO_MIME);
		$mimetype = finfo_file($finfo, $file_full_name);
		if ($mimetype === false) {
			echoFileNotFound($file_name);
			return;
		}
		finfo_close($finfo);

		echo '<h4>', htmlspecialchars($file_name), '<sub> ', $mimetype, '</sub></h4>';
		echo "<pre>\n";

		$output_limit = 1000;
		if (strStartWith($mimetype, 'text/')) {
			echo htmlspecialchars(uojFilePreview($file_full_name, $output_limit));
		} else {
			echo htmlspecialchars(strOmit(shell_exec('xxd -g 4 -l 5000 ' . escapeshellarg($file_full_name) . ' | head -c ' . ($output_limit + 4)), $output_limit));
		}
		echo "\n</pre>";
	}

	$info_form = new UOJForm('info');
	$http_host = HTML::escape(UOJContext::httpHost());
	$download_url = HTML::url("/download.php?type=problem&id={$problem['id']}");
	$info_form->appendHTML(<<<EOD
<div class="form-group">
	<label class="col-sm-3 control-label">SVN地址</label>
	<div class="col-sm-9">
		<div class="form-control-static">
			<button id="button-getsvn" type="button" class="btn btn-info btn-xs pull-right">把我的svn密码发到我的邮箱</button>
			<a>svn://{$http_host}/problem/{$problem['id']}</a>
		</div>
	</div>
</div>
<script type="text/javascript">
$('#button-getsvn').click(function(){
	if (!confirm("确定要发送你的svn密码到${myUser['email']}吗")) {
		return;
	}
	$.post('${_SERVER['REQUEST_URI']}', {
		getsvn : ''
	}, function(res) {
		if (res == "good") {
			BootstrapDialog.show({
				title   : "操作成功",
				message : "svn密码已经发送至您的邮箱，请查收。",
				type    : BootstrapDialog.TYPE_SUCCESS,
				buttons: [{
					label: '好的',
					action: function(dialog) {
						dialog.close();
					}
				}],
			});
		} else {
			BootstrapDialog.show({
				title   : "操作失败",
				message : "邮件未发送成功",
				type    : BootstrapDialog.TYPE_DANGER,
				buttons: [{
					label: '好吧',
					action: function(dialog) {
						dialog.close();
					}
				}],
			});
		}
	});
});
</script>
EOD
	);
	$info_form->appendHTML(<<<EOD
<div class="form-group">
	<label class="col-sm-3 control-label">problem_{$problem['id']}.zip</label>
	<div class="col-sm-9">
		<div class="form-control-static">
			<a href="$download_url">$download_url</a>
		</div>
	</div>
</div>
EOD
	);
	$info_form->appendHTML(<<<EOD
<div class="form-group">
	<label class="col-sm-3 control-label">testlib.h</label>
	<div class="col-sm-9">
		<div class="form-control-static">
			<a href="/download.php?type=testlib.h">下载</a>
		</div>
	</div>
</div>
EOD
	);

	$esc_submission_requirement = HTML::escape(json_encode(json_decode($problem['submission_requirement']), JSON_PRETTY_PRINT));
	$info_form->appendHTML(<<<EOD
<div class="form-group">
	<label class="col-sm-3 control-label">提交文件配置</label>
	<div class="col-sm-9">
		<div class="form-control-static"><pre>
$esc_submission_requirement
</pre>
		</div>
	</div>
</div>
EOD
	);
	$esc_extra_config = HTML::escape(json_encode(json_decode($problem['extra_config']), JSON_PRETTY_PRINT));
	$info_form->appendHTML(<<<EOD
<div class="form-group">
	<label class="col-sm-3 control-label">其它配置</label>
	<div class="col-sm-9">
		<div class="form-control-static"><pre>
$esc_extra_config
</pre>
		</div>
	</div>
</div>
EOD
	);
	if (isSuperUser($myUser)) {
		$info_form->addVInput('submission_requirement', 'text', '提交文件配置', $problem['submission_requirement'],
			function ($submission_requirement, &$vdata) {
				$submission_requirement = json_decode($submission_requirement, true);
				if ($submission_requirement === null) {
					return '不是合法的JSON';
				}
				$vdata['submission_requirement'] = json_encode($submission_requirement);
			},
			null);
		$info_form->addVInput('extra_config', 'text', '其它配置', $problem['extra_config'],
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
			$esc_submission_requirement = DB::escape($vdata['submission_requirement']);
			$esc_extra_config = DB::escape($vdata['extra_config']);
			DB::update("update problems set submission_requirement = '$esc_submission_requirement', extra_config = '$esc_extra_config' where id = {$problem['id']}");
		};
	} else {
		$info_form->no_submit = true;
	}

	class DataDisplayer {
		public $problem_conf = array();
		public $data_files = array();
		public $displayers = array();

		public function __construct($problem_conf = null, $data_files = null) {
			global $data_dir;

			if (isset($problem_conf)) {
				foreach ($problem_conf as $key => $val) {
					$this->problem_conf[$key] = array('val' => $val);
				}
			}

			if (!isset($data_files)) {
				$this->data_files = array_filter(scandir($data_dir), function($x){return $x !== '.' && $x !== '..' && $x !== 'problem.conf';});
				natsort($this->data_files);
				array_unshift($this->data_files, 'problem.conf');
			} else {
				$this->data_files = $data_files;
			}

			$this->setDisplayer('problem.conf', function($self) {
				global $info_form;
				$info_form->printHTML();
				echo '<div class="top-buffer-md"></div>';

				echo '<table class="table table-bordered table-hover table-striped table-text-center">';
				echo '<thead>';
				echo '<tr>';
				echo '<th>key</th>';
				echo '<th>value</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
				foreach ($self->problem_conf as $key => $info) {
					if (!isset($info['status'])) {
						echo '<tr>';
						echo '<td>', htmlspecialchars($key), '</td>';
						echo '<td>', htmlspecialchars($info['val']), '</td>';
						echo '</tr>';
					} elseif ($info['status'] == 'danger') {
						echo '<tr class="text-danger">';
						echo '<td>', htmlspecialchars($key), '</td>';
						echo '<td>', htmlspecialchars($info['val']), ' <span class="glyphicon glyphicon-remove"></span>', '</td>';
						echo '</tr>';
					}
				}
				echo '</tbody>';
				echo '</table>';

				echoFilePre('problem.conf');
			});
		}

		public function setProblemConfRowStatus($key, $status) {
			$this->problem_conf[$key]['status'] = $status;
			return $this;
		}

		public function setDisplayer($file_name, $fun) {
			$this->displayers[$file_name] = $fun;
			return $this;
		}
		public function addDisplayer($file_name, $fun) {
			$this->data_files[] = $file_name;
			$this->displayers[$file_name] = $fun;
			return $this;
		}
		public function echoDataFilesList($active_file) {
			foreach ($this->data_files as $file_name) {
				if ($file_name != $active_file) {
					echo '<li>';
				} else {
					echo '<li class="active">';
				}
				echo '<a href="#">', htmlspecialchars($file_name), '</a>', '</li>';
			}
		}
		public function displayFile($file_name) {
			global $data_dir;

			if (isset($this->displayers[$file_name])) {
				$fun = $this->displayers[$file_name];
				$fun($this);
			} elseif (in_array($file_name, $this->data_files)) {
				echoFilePre($file_name);
			} else {
				echoFileNotFound($file_name);
			}
		}
	}

	function getDataDisplayer() {
		global $data_dir;
		global $problem;

		$allow_files = array_flip(array_filter(scandir($data_dir), function($x){return $x !== '.' && $x !== '..';}));

		$getDisplaySrcFunc = function($name) use($allow_files) {
			return function() use($name, $allow_files) {
				$src_name = $name . '.cpp';
				if (isset($allow_files[$src_name])) {
					echoFilePre($src_name);
				} else {
					echoFileNotFound($src_name);
				}
				if (isset($allow_files[$name])) {
					echoFilePre($name);
				} else {
					echoFileNotFound($name);
				}
			};
		};

		$problem_conf = getUOJConf("$data_dir/problem.conf");
		if ($problem_conf === -1) {
			return (new DataDisplayer())->setDisplayer('problem.conf', function() {
				global $info_form;
				$info_form->printHTML();
				echoFileNotFound('problem.conf');
			});
		}
		if ($problem_conf === -2) {
			return (new DataDisplayer())->setDisplayer('problem.conf', function() {
				global $info_form;
				$info_form->printHTML();
				echo '<h4 class="text-danger">problem.conf 格式有误</h4>';
				echoFilePre('problem.conf');
			});
		}

		$judger_name = getUOJConfVal($problem_conf, 'use_builtin_judger', null);
		if (!isset($problem_conf['use_builtin_judger'])) {
			return new DataDisplayer($problem_conf);
		}
		if ($problem_conf['use_builtin_judger'] == 'on') {
			$n_tests = getUOJConfVal($problem_conf, 'n_tests', 10);
			if (!validateUInt($n_tests)) {
				return (new DataDisplayer($problem_conf))->setProblemConfRowStatus('n_tests', 'danger');
			}

			$has_extra_tests = !(isset($problem_conf['submit_answer']) && $problem_conf['submit_answer'] == 'on');

			$data_disp = new DataDisplayer($problem_conf, array('problem.conf'));
			$data_disp->addDisplayer('tests',
				function($self) use($problem_conf, $allow_files, $n_tests, $n_ex_tests) {
					for ($num = 1; $num <= $n_tests; $num++) {
						$input_file_name = getUOJProblemInputFileName($problem_conf, $num);
						$output_file_name = getUOJProblemOutputFileName($problem_conf, $num);
						echo '<div class="row">';
						echo '<div class="col-md-6">';
						if (isset($allow_files[$input_file_name])) {
							echoFilePre($input_file_name);
						} else {
							echoFileNotFound($input_file_name);
						}
						echo '</div>';
						echo '<div class="col-md-6">';
						if (isset($allow_files[$output_file_name])) {
							echoFilePre($output_file_name);
						} else {
							echoFileNotFound($output_file_name);
						}
						echo '</div>';
						echo '</div>';
					}
				}
			);
			if ($has_extra_tests) {
				$n_ex_tests = getUOJConfVal($problem_conf, 'n_ex_tests', 0);
				if (!validateUInt($n_ex_tests)) {
					return (new DataDisplayer($problem_conf))->setProblemConfRowStatus('n_ex_tests', 'danger');
				}

				$data_disp->addDisplayer('extra tests',
					function($self) use($problem_conf, $allow_files, $n_tests, $n_ex_tests) {
						for ($num = 1; $num <= $n_ex_tests; $num++) {
							$input_file_name = getUOJProblemExtraInputFileName($problem_conf, $num);
							$output_file_name = getUOJProblemExtraOutputFileName($problem_conf, $num);
							echo '<div class="row">';
							echo '<div class="col-md-6">';
							if (isset($allow_files[$input_file_name])) {
								echoFilePre($input_file_name);
							} else {
								echoFileNotFound($input_file_name);
							}
							echo '</div>';
							echo '<div class="col-md-6">';
							if (isset($allow_files[$output_file_name])) {
								echoFilePre($output_file_name);
							} else {
								echoFileNotFound($output_file_name);
							}
							echo '</div>';
							echo '</div>';
						}
					}
				);
			}
			
			if (!isset($problem_conf['interaction_mode'])) {
				if (isset($problem_conf['use_builtin_checker'])) {
					$data_disp->addDisplayer('checker', function($self) {
						echo '<h4>use builtin checker : ', $self->problem_conf['use_builtin_checker']['val'], '</h4>';
					});
				} else {
					$data_disp->addDisplayer('checker', $getDisplaySrcFunc('chk'));
				}
			}
			if ($problem['hackable']) {
				$data_disp->addDisplayer('standard', $getDisplaySrcFunc('std'));
				$data_disp->addDisplayer('validator', $getDisplaySrcFunc('val'));
			}
			if (isset($problem_conf['interaction_mode'])) {
				$data_disp->addDisplayer('interactor', $getDisplaySrcFunc('interactor'));
			}
			return $data_disp;
		} else {
			return (new DataDisplayer($problem_conf))->setProblemConfRowStatus('use_builtin_judger', 'danger');
		}
	}

	$data_disp = getDataDisplayer();

	if (isset($_GET['display_file'])) {
		if (!isset($_GET['file_name'])) {
			echoFileNotFound('');
		} else {
			$data_disp->displayFile($_GET['file_name']);
		}
		die();
	}

	$hackable_form = new UOJForm('hackable');
	$hackable_form->handle = function() {
		global $problem;
		$problem['hackable'] = !$problem['hackable'];
		$ret = svnSyncProblemData($problem);
		if ($ret) {
			becomeMsgPage('<div>' . $ret . '</div><a href="/problem/'.$problem['id'].'/manage/data">返回</a>');
		}
		
		$hackable = $problem['hackable'] ? 1 : 0;
		mysql_query("update problems set hackable = $hackable where id = ${problem['id']}");
	};
	$hackable_form->submit_button_config['class_str'] = 'btn btn-warning btn-block';
	$hackable_form->submit_button_config['text'] = $problem['hackable'] ? '禁止使用hack' : '允许使用hack';
	$hackable_form->submit_button_config['smart_confirm'] = '';

	$data_form = new UOJForm('data');
	$data_form->handle = function() {
		global $problem, $myUser;
		set_time_limit(60 * 5);
		$ret = svnSyncProblemData($problem, $myUser);
		if ($ret) {
			becomeMsgPage('<div>' . $ret . '</div><a href="/problem/'.$problem['id'].'/manage/data">返回</a>');
		}
	};
	$data_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
	$data_form->submit_button_config['text'] = '与svn仓库同步';
	$data_form->submit_button_config['smart_confirm'] = '';
	
	$clear_data_form = new UOJForm('clear_data');
	$clear_data_form->handle = function() {
		global $problem;
		svnClearProblemData($problem);
	};
	$clear_data_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
	$clear_data_form->submit_button_config['text'] = '清空题目数据';
	$clear_data_form->submit_button_config['smart_confirm'] = '';

	$rejudge_form = new UOJForm('rejudge');
	$rejudge_form->handle = function() {
		global $problem;
		rejudgeProblem($problem);
	};
	$rejudge_form->succ_href = "/submissions?problem_id={$problem['id']}";
	$rejudge_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
	$rejudge_form->submit_button_config['text'] = '重测该题';
	$rejudge_form->submit_button_config['smart_confirm'] = '';
	
	$rejudgege97_form = new UOJForm('rejudgege97');
	$rejudgege97_form->handle = function() {
		global $problem;
		rejudgeProblemGe97($problem);
	};
	$rejudgege97_form->succ_href = "/submissions?problem_id={$problem['id']}";
	$rejudgege97_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
	$rejudgege97_form->submit_button_config['text'] = '重测 >=97 的程序';
	$rejudgege97_form->submit_button_config['smart_confirm'] = '';
	
	$view_type_form = new UOJForm('view_type');
	$view_type_form->addVSelect('view_content_type',
		array('NONE' => '禁止',
				'SELF' => '仅自己',
				'ALL_AFTER_AC' => 'AC后',
				'ALL' => '所有人'
		),
		'查看提交文件:',
		$problem_extra_config['view_content_type']
	);
	$view_type_form->addVSelect('view_all_details_type',
		array('NONE' => '禁止',
				'SELF' => '仅自己',
				'ALL_AFTER_AC' => 'AC后',
				'ALL' => '所有人'
		),
		'查看全部详细信息:',
		$problem_extra_config['view_all_details_type']
	);
	$view_type_form->addVSelect('view_details_type',
		array('NONE' => '禁止',
				'SELF' => '仅自己',
				'ALL_AFTER_AC' => 'AC后',
				'ALL' => '所有人'
		),
		'查看测试点详细信息:',
		$problem_extra_config['view_details_type']
	);
	$view_type_form->handle = function() {
		global $problem, $problem_extra_config;
		$config = $problem_extra_config;
		$config['view_content_type'] = $_POST['view_content_type'];
		$config['view_all_details_type'] = $_POST['view_all_details_type'];
		$config['view_details_type'] = $_POST['view_details_type'];
		$esc_config = DB::escape(json_encode($config));
		mysql_query("update problems set extra_config = '$esc_config' where id = '{$problem['id']}'");
	};
	$view_type_form->submit_button_config['class_str'] = 'btn btn-warning btn-block top-buffer-sm';
	
	if ($problem['hackable']) {
		$test_std_form = new UOJForm('test_std');
		$test_std_form->handle = function() {
			global $myUser, $problem;
			
			$user_std = queryUser('std');
			if (!$user_std) {
				becomeMsgPage('Please create an user named "std"');
			}
			
			$requirement = json_decode($problem['submission_requirement'], true);
			
			$zip_file_name = uojRandAvaiableSubmissionFileName();
			$zip_file = new ZipArchive();
			if ($zip_file->open(UOJContext::storagePath().$zip_file_name, ZipArchive::CREATE) !== true) {
				becomeMsgPage('提交失败');
			}
		
			$content = array();
			$content['file_name'] = $zip_file_name;
			$content['config'] = array();
			foreach ($requirement as $req) {
				if ($req['type'] == "source code") {
					$content['config'][] = array("{$req['name']}_language", "C++");
				}
			}
		
			$tot_size = 0;
			foreach ($requirement as $req) {
				$zip_file->addFile("/var/uoj_data/{$problem['id']}/std.cpp", $req['file_name']);
				$tot_size += $zip_file->statName($req['file_name'])['size'];
			}
		
			$zip_file->close();
		
			$content['config'][] = array('validate_input_before_test', 'on');
			$content['config'][] = array('problem_id', $problem['id']);
			$esc_content = DB::escape(json_encode($content));
			$esc_language = DB::escape('C++');
		 	
		 	$result = array();
		 	$result['status'] = "Waiting";
		 	$result_json = json_encode($result);
			$is_hidden = $problem['is_hidden'] ? 1 : 0;
			
			DB::insert("insert into submissions (problem_id, submit_time, submitter, content, language, tot_size, status, result, is_hidden) values ({$problem['id']}, now(), '{$user_std['username']}', '$esc_content', '$esc_language', $tot_size, '{$result['status']}', '$result_json', $is_hidden)");
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
?>
<?php
	$REQUIRE_LIB['dialog'] = '';
?>
<?php echoUOJPageHeader(HTML::stripTags($problem['title']) . ' - 数据 - 题目管理') ?>
<h1 class="page-header" align="center">#<?=$problem['id']?> : <?=$problem['title']?> 管理</h1>
<ul class="nav nav-tabs" role="tablist">
	<li><a href="/problem/<?= $problem['id'] ?>/manage/statement" role="tab">编辑</a></li>
	<li><a href="/problem/<?= $problem['id'] ?>/manage/managers" role="tab">管理者</a></li>
	<li class="active"><a href="/problem/<?= $problem['id'] ?>/manage/data" role="tab">数据</a></li>
	<li><a href="/problem/<?=$problem['id']?>" role="tab">返回</a></li>
</ul>

<div class="row">
	<div class="col-md-10 top-buffer-sm">
		<div class="row">
			<div class="col-md-3 top-buffer-sm" id="div-file_list">
				<ul class="nav nav-pills nav-stacked">
					<?php $data_disp->echoDataFilesList('problem.conf'); ?>
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
				<span class="glyphicon glyphicon-remove"></span> hack功能已禁止
			<?php endif ?>
			<?php $hackable_form->printHTML() ?>
		</div>
		<div class="top-buffer-md">
		<?php if ($problem['hackable']): ?>
			<?php $test_std_form->printHTML() ?>
		<?php endif ?>
		</div>
		<div class="top-buffer-md">
			<button id="button-display_view_type" type="button" class="btn btn-primary btn-block" onclick="$('#div-view_type').toggle('fast');">修改提交记录可视权限</button>
			<div class="top-buffer-sm" id="div-view_type" style="display:none; padding-left:5px; padding-right:5px;">
				<?php $view_type_form->printHTML(); ?>
			</div>
		</div>
		<div class="top-buffer-md">
			<?php $data_form->printHTML(); ?>
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
</div>
<?php echoUOJPageFooter() ?>
