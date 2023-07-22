<?php

// Actually, these things should be done by main_judger so that the code would be much simpler.
// However, this class exists due to some history issues.
class UOJProblemDataSynchronizer {
	private UOJProblem $problem;
	private UOJProblemCandidateDataManager $candidate_data_manager;
	private $user; // array, null, or "root"
	private int $id;
	private string $svn_data_dir_outer, $svn_data_dir, $data_dir, $prepare_dir;
	private string $upload_dir_outer, $upload_dir;
	
	private $requirement, $problem_extra_config;
	private $problem_conf, $final_problem_conf;
	private $allow_files;
	
	public function retryMsg() {
		return '请等待上一次svn同步或数据上传操作结束后重试';
	}
	
	public function __construct(UOJProblem $problem, $user = null) {
		$this->problem = $problem;
		$this->candidate_data_manager = $this->problem->getCandidateDataManager();
		$this->user = $user;
		
		if (!validateUInt($this->problem->info['id'])) {
			UOJLog::error("svnSyncProblemData: hacker detected");
			return;
		}
		$this->id = (int)$this->problem->info['id'];
		
		$this->svn_data_dir_outer = $this->candidate_data_manager->workCopyPath();
		$this->svn_data_dir = $this->candidate_data_manager->dataPath();
		$this->data_dir = "/var/uoj_data/{$this->id}";
		$this->prepare_dir = "/var/uoj_data/prepare_{$this->id}";
		$this->upload_dir_outer = "/var/uoj_data/prepare_{$this->id}/upload";
		$this->upload_dir = "/var/uoj_data/prepare_{$this->id}/upload/1";
	}
	
	/**
	 * $type can be either LOCK_SH or LOCK_EX
	*/
	private function lock($type, $func) {
		$ret = FS::lock_file("/var/uoj_data/{$this->id}_lock", $type, $func);
		return $ret === false ? $this->retryMsg() : $ret;
	}
	
	private function check_conf_on($name) {
		return (new UOJProblemConf($this->problem_conf))->isOn($name);
	}
	
	private function create_prepare_folder() {
		return mkdir($this->prepare_dir, 0755);
	}
	private function remove_prepare_folder() {
		return UOJLocalRun::exec(['rm', $this->prepare_dir, '-rf']);
	}

	private function copy_to_prepare($file_name) {
		if (!isset($this->allow_files[$file_name])) {
			throw new UOJFileNotFoundException($file_name);
		}
		$src = "{$this->svn_data_dir}/$file_name";
		$dest = "{$this->prepare_dir}/$file_name";
		if (file_exists($dest)) {
			return;
		}
		if (isset($this->problem_extra_config['dont_use_formatter']) || !is_file($src)) {
			$ret = UOJLocalRun::exec(['cp', $src, $dest, '-r']);
		} else {
			$ret = UOJLocalRun::formatter($src, $dest);
		}
		if ($ret === false) {
			throw new UOJFileNotFoundException($file_name);
		}
	}
	private function copy_file_to_prepare($file_name) {
		if (!is_file("{$this->svn_data_dir}/$file_name")) {
			throw new UOJFileNotFoundException($file_name);
		}
		$this->copy_to_prepare($file_name);
	}
	private function copy_source_code_to_prepare($code_name) { // file name without suffix
		$src = UOJLang::findSourceCode($code_name, $this->svn_data_dir);
		if ($src === false) {
			throw new UOJFileNotFoundException($code_name);
		}
		$this->copy_to_prepare($src['path']);
	}

	private function compile_at_prepare($name, $config=[]) {
		$include_path = UOJLocalRun::$judger_include_path;

		$src = UOJLang::findSourceCode($name, $this->prepare_dir);

		if (isset($config['path'])) {
			if (rename("{$this->prepare_dir}/{$src['path']}", "{$this->prepare_dir}/{$config['path']}/{$src['path']}") === false) {
				throw new Exception("<strong>$name</strong> : move failed");
			}
			$work_path = "{$this->prepare_dir}/{$config['path']}";
		} else {
			$work_path = $this->prepare_dir;
		}

		$compile_options = [
			['custom', UOJLocalRun::$judger_run_path]
		];
		$runp_options = [
			['in', '/dev/null'],
			['out', 'stderr'],
			['err', "{$this->prepare_dir}/compiler_result.txt"],
			['tl', 60],
			['ml', 512],
			['ol', 64],
			['type', 'compiler'],
			['work-path', $work_path],
		];
		if (!empty($config['need_include_header'])) {
			$compile_options[] = ['cinclude', $include_path];
			$runp_options[] = ['add-readable-raw', "{$include_path}/"];
		}
		if (!empty($config['implementer'])) {
			$compile_options[] = ['impl', $config['implementer']];
		}
		$res = UOJLocalRun::compile($name, $compile_options, $runp_options);
		$this->final_problem_conf["{$name}_run_type"] = UOJLang::getRunTypeFromLanguage($src['lang']);
		$rstype = isset($res['rstype']) ? $res['rstype'] : 7;

		if ($rstype != 0 || $res['exit_code'] != 0) {
			if ($rstype == 0) {
				throw new Exception("<strong>$name</strong> : compile error<pre>\n" . HTML::escape(uojFilePreview("{$this->prepare_dir}/compiler_result.txt", 10000)) . "\n</pre>");
			} elseif ($rstype == 7) {
				throw new Exception("<strong>$name</strong> : compile error. No comment");
			} else {
				throw new Exception("<strong>$name</strong> : compile error. Compiler " . judgerCodeStr($rstype));
			}
		}
		
		unlink("{$this->prepare_dir}/compiler_result.txt");
		
		if (isset($config['path'])) {
			rename("{$this->prepare_dir}/{$config['path']}/{$src['path']}", "{$this->prepare_dir}/{$src['path']}");
			rename("{$this->prepare_dir}/{$config['path']}/$name", "{$this->prepare_dir}/$name");
		}
	}
	private function makefile_at_prepare() {
		$include_path = UOJLocalRun::$judger_include_path;
		$res = UOJLocalRun::exec(['/usr/bin/make', "INCLUDE_PATH={$include_path}"], [
			['in', '/dev/null'],
			['out', 'stderr'],
			['err', "{$this->prepare_dir}/makefile_result.txt"],
			['tl', 60],
			['ml', 512],
			['ol', 64],
			['type', 'compiler'],
			['work-path', $this->prepare_dir],
			['add-readable-raw', "{$include_path}/"]
		]);
		$rstype = isset($res['rstype']) ? $res['rstype'] : 7;
		
		if ($rstype != 0 || $res['exit_code'] != 0) {
			if ($rstype == 0) {
				throw new Exception("<strong>Makefile</strong> : compile error<pre>\n" . HTML::escape(uojFilePreview("{$this->prepare_dir}/makefile_result.txt", 10000)) . "\n</pre>");
			} elseif ($rstype == 7) {
				throw new Exception("<strong>Makefile</strong> : compile error. No comment");
			} else {
				throw new Exception("<strong>Makefile</strong> : compile error. Compiler " . judgerCodeStr($rstype));
			}
		}
		
		unlink("{$this->prepare_dir}/makefile_result.txt");
	}
	
	private function create_upload_folder() {
		if (!$this->create_prepare_folder()
			|| !mkdir($this->upload_dir_outer, 0755)
			|| !mkdir($this->upload_dir, 0755)) {
			throw new UOJUploadFailedException('创建临时文件夹失败');
		}
	}
	
	private function commit_upload_folder($msg = 'upload via browser') {
		if (!$this->candidate_data_manager->start_update()) {
			return false;
		}
		$ret = UOJLocalRun::exec(['cp', '-rf', $this->upload_dir, $this->svn_data_dir_outer]); # for uoj.ac, we use -rlf
		if (!$this->candidate_data_manager->end_update($msg)) {
			$ret = false;
		}
		return $ret;
	}
	
	private function _upload($new_data_zip) {
		try {
			$this->create_upload_folder();
		
			$zip = new ZipArchive;
			if ($zip->open($new_data_zip) !== true) {
				throw new UOJUploadFailedException('压缩文件打开失败');
			}
			if (!$zip->extractTo($this->upload_dir)) {
				throw new UOJUploadFailedException('压缩文件解压失败');
			}
			if (!$zip->close()) {
				throw new UOJUploadFailedException('压缩文件关闭失败');
			}

			$files = FS::scandir($this->upload_dir);
			if (count($files) == 1 && is_dir("{$this->upload_dir}/{$files[0]}")) {
				if (!FS::moveFilesInDir("{$this->upload_dir}/{$files[0]}", $this->upload_dir)) {
					throw new UOJUploadFailedException('操作解压后的文件时发生错误');
				}
			}
			
			$this->commit_upload_folder();
			
			return '';
		} catch (Exception $e) {
			return $e->getMessage();
		} finally {
			$this->remove_prepare_folder();
		}
	}
	public function upload($new_data_zip) {
		return $this->lock(LOCK_EX, fn() => $this->_upload($new_data_zip));
	}
	
	public function _updateFromArray($data) {
		try {
			$this->create_upload_folder();
			foreach ($data as $file_name => $content) {
				if ($file_name == 'problem.conf') {
					putUOJConf("{$this->upload_dir}/problem.conf", $content);
				} else {
					// assume $data is from a trusted source
					file_put_contents("{$this->upload_dir}/$file_name", $content);
				}
			}
			$this->commit_upload_folder();
			return '';
		} catch (Exception $e) {
			return $e->getMessage();
		} finally {
			$this->remove_prepare_folder();
		}
	}
	public function updateFromArray($data) {
		return $this->lock(LOCK_EX, fn() => $this->_updateFromArray($data));
	}

	private function _addHackPoint($uploaded_input_file, $uploaded_output_file, $reason) {
		try {
			switch ($this->problem->getExtraConfig('add_hack_as')) {
				case 'test':
					$key_num = 'n_tests';
					$msg = 'add new test';
					$gen_in_name = 'getUOJProblemInputFileName';
					$gen_out_name = 'getUOJProblemOutputFileName';
					break;
				case 'ex_test':
					$key_num = 'n_ex_tests';
					$msg = 'add new extra test';
					$gen_in_name = 'getUOJProblemExtraInputFileName';
					$gen_out_name = 'getUOJProblemExtraOutputFileName';
					break;
				default:
					return 'add hack to data failed: add_hack_as should be either "ex_test" or "test"';
			}

			$this->create_upload_folder();

			$new_problem_conf = $this->problem->getProblemConfArray();
			if ($new_problem_conf == -1 || $new_problem_conf == -2) {
				return $new_problem_conf;
			}
			$new_problem_conf[$key_num] = getUOJConfVal($new_problem_conf, $key_num, 0) + 1;
			
			putUOJConf("{$this->upload_dir}/problem.conf", $new_problem_conf);
		
			$new_input_name = $gen_in_name($new_problem_conf, $new_problem_conf[$key_num]);
			$new_output_name = $gen_out_name($new_problem_conf, $new_problem_conf[$key_num]);

			if (!copy($uploaded_input_file, "{$this->upload_dir}/$new_input_name")) {
				return "input file not found";
			}
			if (!copy($uploaded_output_file, "{$this->upload_dir}/$new_output_name")) {
				return "output file not found";
			}

			if ($this->commit_upload_folder($msg) === false) {
				return 'svn failed';
			}
		} catch (Exception $e) {
			return $e->getMessage();
		} finally {
			$this->remove_prepare_folder();
		}

		$ret = $this->_sync();
		if ($ret !== '') {
			return "hack successfully but sync failed: $ret";
		}

		if (isset($reason['hack_url'])) {
			UOJSystemUpdate::updateProblem($this->problem, [
				'text' => 'hack成功，自动添加数据',
				'url' => $reason['hack_url']
			]);
		}
		UOJSubmission::rejudgeProblemAC($this->problem, [
			'reason_text' => $reason['rejudge'],
			'requestor' => ''
		]);
		return '';
	}
	
	public function addHackPoint($uploaded_input_file, $uploaded_output_file, $reason = []) {
		return $this->lock(LOCK_EX, fn() => $this->_addHackPoint($uploaded_input_file, $uploaded_output_file, $reason));
	}

	public function fast_hackable_check() {
		if (!$this->problem->info['hackable']) {
			return;
		}
		if (!$this->check_conf_on('use_builtin_judger')) {
			return;
		}

		if ($this->check_conf_on('submit_answer')) {
			throw new UOJProblemConfException("提交答案题不可 Hack，请先停用本题的 Hack 功能。");
		} else {
			if (UOJLang::findSourceCode('std', $this->svn_data_dir) === false) {
				throw new UOJProblemConfException("找不到本题的 std。请上传 std 代码文件，或停用本题的 Hack 功能。");
			}
			if (UOJLang::findSourceCode('val', $this->svn_data_dir) === false) {
				throw new UOJProblemConfException("找不到本题的 val。请上传 val 代码文件，或停用本题的 Hack 功能。");
			}
		}
	}
	
	private function _sync() {
		try {
			if (!$this->create_prepare_folder()) {
				throw new UOJSyncFailedException('创建临时文件夹失败');
			}
		
			$this->requirement = [];
			$this->problem_extra_config = $this->problem->getExtraConfig();;
			if (!is_file("{$this->svn_data_dir}/problem.conf")) {
				throw new UOJFileNotFoundException("problem.conf");
			}

			$this->problem_conf = getUOJConf("{$this->svn_data_dir}/problem.conf");
			$this->final_problem_conf = $this->problem_conf;
			if ($this->problem_conf === -1) {
				throw new UOJFileNotFoundException("problem.conf");
			} elseif ($this->problem_conf === -2) {
				throw new UOJProblemConfException("syntax error: duplicate keys");
			}

			$this->allow_files = array_flip(FS::scandir($this->svn_data_dir));

			$zip_file = new ZipArchive();
			if ($zip_file->open("{$this->prepare_dir}/download.zip", ZipArchive::CREATE) !== true) {
				throw new Exception("<strong>download.zip</strong> : failed to create the zip file");
			}
			
			if (isset($this->allow_files['require']) && is_dir("{$this->svn_data_dir}/require")) {
				$this->copy_to_prepare('require');
			}
			
			if (isset($this->allow_files['testlib.h']) && is_file("{$this->svn_data_dir}/testlib.h")) {
				$this->copy_file_to_prepare('testlib.h');
			}

			$this->fast_hackable_check();

			if ($this->check_conf_on('use_builtin_judger')) {
				$n_tests = getUOJConfVal($this->problem_conf, 'n_tests', 10);
				if (!validateUInt($n_tests) || $n_tests <= 0) {
					throw new UOJProblemConfException("n_tests must be a positive integer");
				}
				for ($num = 1; $num <= $n_tests; $num++) {
					$input_file_name = getUOJProblemInputFileName($this->problem_conf, $num);
					$output_file_name = getUOJProblemOutputFileName($this->problem_conf, $num);

					$this->copy_file_to_prepare($input_file_name);
					$this->copy_file_to_prepare($output_file_name);
				}

				if (!$this->check_conf_on('interaction_mode')) {
					if (isset($this->problem_conf['use_builtin_checker'])) {
						if (!preg_match('/^[a-zA-Z0-9_]{1,20}$/', $this->problem_conf['use_builtin_checker'])) {
							throw new Exception("<strong>" . HTML::escape($this->problem_conf['use_builtin_checker']) . "</strong> is not a valid checker");
						}
					} else {
						$this->copy_source_code_to_prepare('chk');
						$this->compile_at_prepare('chk', ['need_include_header' => true]);
					}
				}
				
				if ($this->check_conf_on('submit_answer')) {
					if (!isset($this->problem_extra_config['dont_download_input'])) {
						for ($num = 1; $num <= $n_tests; $num++) {
							$input_file_name = getUOJProblemInputFileName($this->problem_conf, $num);
							$zip_file->addFile("{$this->prepare_dir}/$input_file_name", "$input_file_name");
						}
					}

					$n_output_files = 0;
					for ($num = 1; $num <= $n_tests; $num++) {
						$output_file_id = getUOJConfVal($this->problem_conf, ["output_file_id_{$num}", "output_file_id"], "$num");
						if (!validateUInt($output_file_id) || $output_file_id < 0 || $output_file_id > $n_tests) {
							throw new UOJProblemConfException("output_file_id/output_file_id_{$num} must be in [1, n_tests]");
						}
						$n_output_files = max($n_output_files, $output_file_id);
					}
					for ($num = 1; $num <= $n_output_files; $num++) {
						$output_file_name = getUOJProblemOutputFileName($this->problem_conf, $num);
						$this->requirement[] = ['name' => "output$num", 'type' => 'text', 'file_name' => $output_file_name];
					}
				} else {
					$n_ex_tests = getUOJConfVal($this->problem_conf, 'n_ex_tests', 0);
					if (!validateUInt($n_ex_tests) || $n_ex_tests < 0) {
						throw new UOJProblemConfException('n_ex_tests must be a non-negative integer. Current value: '.HTML::escape($n_ex_tests));
					}

					for ($num = 1; $num <= $n_ex_tests; $num++) {
						$input_file_name = getUOJProblemExtraInputFileName($this->problem_conf, $num);
						$output_file_name = getUOJProblemExtraOutputFileName($this->problem_conf, $num);

						$this->copy_file_to_prepare($input_file_name);
						$this->copy_file_to_prepare($output_file_name);
					}

					if ($this->problem->info['hackable']) {
						$this->copy_source_code_to_prepare('std');
						if (isset($this->problem_conf['with_implementer']) && $this->problem_conf['with_implementer'] == 'on') {
							$this->compile_at_prepare('std', [
								'implementer' => 'implementer',
								'path' => 'require'
							]);
						} else {
							$this->compile_at_prepare('std');
						}
						$this->copy_source_code_to_prepare('val');
						$this->compile_at_prepare('val', ['need_include_header' => true]);
					}
					
					if ($this->check_conf_on('interaction_mode')) {
						$this->copy_source_code_to_prepare('interactor');
						$this->compile_at_prepare('interactor', ['need_include_header' => true]);
					}

					$n_sample_tests = getUOJConfVal($this->problem_conf, 'n_sample_tests', $n_tests);
					if (!validateUInt($n_sample_tests) || $n_sample_tests < 0) {
						throw new UOJProblemConfException('n_sample_tests must be a non-negative integer. Current value: '.HTML::escape($n_sample_tests));
					}
					if ($n_sample_tests > $n_ex_tests) {
						throw new UOJProblemConfException("n_sample_tests can't be greater than n_ex_tests");
					}

					if (!isset($this->problem_extra_config['dont_download_sample'])) {
						for ($num = 1; $num <= $n_sample_tests; $num++) {
							$input_file_name = getUOJProblemExtraInputFileName($this->problem_conf, $num);
							$output_file_name = getUOJProblemExtraOutputFileName($this->problem_conf, $num);
							$zip_file->addFile("{$this->prepare_dir}/{$input_file_name}", "$input_file_name");
							if (!isset($this->problem_extra_config['dont_download_sample_output'])) {
								$zip_file->addFile("{$this->prepare_dir}/{$output_file_name}", "$output_file_name");
							}
						}
					}

					$this->requirement[] = ['name' => 'answer', 'type' => 'source code', 'file_name' => 'answer.code'];
				}
			} else {
				if ($this->user !== 'root' && !isSuperUser($this->user)) {
					throw new UOJProblemConfException("use_builtin_judger must be on.");
				} else {
					foreach ($this->allow_files as $file_name => $file_num) {
						$this->copy_to_prepare($file_name);
					}
					$this->makefile_at_prepare();
					
					$this->requirement[] = ['name' => 'answer', 'type' => 'source code', 'file_name' => 'answer.code'];
				}
			}
			putUOJConf("{$this->prepare_dir}/problem.conf", $this->final_problem_conf);

			if (isset($this->allow_files['download']) && is_dir("{$this->svn_data_dir}/download")) {
				$download_dir = "{$this->svn_data_dir}/download";
				foreach (FS::scandir_r($download_dir) as $file_name) {
					if (is_file("{$download_dir}/{$file_name}")) {
						$zip_file->addFile("{$download_dir}/{$file_name}", $file_name);
					}
				}
			}
			
			$zip_file->close();

			$orig_requirement = $this->problem->getSubmissionRequirement();
			if (!$orig_requirement) {
				DB::update([
					"update problems",
					"set", ["submission_requirement" => json_encode($this->requirement)],
					"where", ["id" => $this->id]
				]);
			}

			UOJSystemUpdate::updateProblemInternally($this->problem, [
				'text' => 'sync',
				'requestor' => Auth::check() ? Auth::id() : null
			]);

		} catch (Exception $e) {
			$this->remove_prepare_folder();
			return $e->getMessage();
		}

		UOJLocalRun::exec(['rm', $this->data_dir, '-r']);
		rename($this->prepare_dir, $this->data_dir);
	
		UOJLocalRun::execAnd([
			['cd', '/var/uoj_data'],
			['zip', "{$this->id}.next.zip", $this->id, '-r', '-q'],
			['mv', "{$this->id}.next.zip", "{$this->id}.zip"],
		]);

		$all = [];
		foreach (glob("{$this->data_dir}/*") as $name) {
			if ($name != "{$this->data_dir}/download.zip") {
				$all[] = $name;
			}
		}
		UOJLocalRun::exec(array_merge(['rm', '-r'], $all));

		return '';
	}
	public function sync() {
		return $this->lock(LOCK_EX, fn() => $this->_sync());
	}
}
