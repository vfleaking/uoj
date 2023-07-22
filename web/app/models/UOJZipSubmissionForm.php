<?php

class UOJZipSubmissionForm extends DropzoneForm {

    public UOJSubmissionArchive $submission_archive;

    public function __construct($form_name, $requirement, $zip_file_name_gen, $handle) {
        parent::__construct($form_name, [], [
			'accept' => <<<EOD
				function(file, done) {
					if (file.size > 0) {
						done();
					} else {
						done('请不要上传空文件！');
					}
				}
				EOD,
        ]);

        $this->introduction = '<p class="top-buffer-md">'.UOJLocale::get(
            'problems::zip file upload introduction',
            '<strong>'.implode(', ', array_map(fn($req) => $req['file_name'], $requirement)).'</strong>'
        ).'</p>';

        $this->handler = function($form) use($requirement, $zip_file_name_gen, $handle) {
            Auth::check() || UOJResponse::page406('请登录后再提交');

            $files = $form->getFiles();
            if (count($files) == 0) {
                UOJResponse::page406('上传出错：请提交至少一个文件');
            }
            
            $reqset = [];
            foreach ($requirement as $req) {
                $file_name = strtolower($req['file_name']);
                $reqset[$file_name] = true;
            }

            $fdict = [];
            $single_file_size_limit = 20 * 1024 * 1024;

            $invalid_zip_msg = '不是合法的zip压缩文件（压缩包里的文件名是否包含特殊字符？或者换个压缩软件试试？）';

            foreach ($files as $name => $file) {
                if (strEndWith(strtolower($name), '.zip')) {
                    $up_zip_file = new ZipArchive();
                    if ($up_zip_file->open($files[$name]['tmp_name']) !== true) {
                        UOJResponse::page406("{$name} {$invalid_zip_msg}");
                    }
                    for ($i = 0; $i < $up_zip_file->numFiles; $i++) {
                        $stat = $up_zip_file->statIndex($i);
                        if ($stat === false) {
                            UOJResponse::page406("{$name} {$invalid_zip_msg}");
                        }
                        $file_name = strtolower(basename($stat['name']));
                        if ($stat['size'] > $single_file_size_limit) {
                            UOJResponse::page406("压缩包内文件 {$file_name} 实际大小过大。");
                        }
                        if ($stat['size'] == 0) { // skip empty files and directories
                            continue;
                        }
                        if (empty($reqset[$file_name])) {
                            UOJResponse::page406("压缩包内包含了题目不需要的文件：{$file_name}");
                        }
                        if (isset($fdict[$file_name])) {
                            UOJResponse::page406("压缩包内的文件出现了重复的文件名：{$file_name}");
                        }
                        $fdict[$file_name] = [
                            'zip' => $up_zip_file,
                            'zip_name' => $name,
                            'size' => $stat['size'],
                            'index' => $i
                        ];
                    }
                }
            }

            foreach ($files as $name => $file) {
                if (!strEndWith(strtolower($name), '.zip')) {
                    $file_name = strtolower($name);
                    if ($file['size'] > $single_file_size_limit) {
                        UOJResponse::page406("文件 {$file_name} 大小过大。");
                    }
                    if ($file['size'] == 0) { // skip empty files
                        continue;
                    }
                    if (empty($reqset[$name])) {
                        UOJResponse::page406("上传了题目不需要的文件：{$file_name}");
                    }
                    if (isset($fdict[$file_name])) {
                        UOJResponse::page406("压缩包内的文件和直接上传的文件中出现了重复的文件名：{$file_name}");
                    }
                    $fdict[$file_name] = [
                        'zip' => false,
                        'size' => $file['size'],
                        'name' => $name
                    ];
                }
            }

            $up_content = [];
            $is_empty = true;
            foreach ($requirement as $req) {
                $file_name = strtolower($req['file_name']);
                if (empty($fdict[$file_name])) {
                    $up_content[$req['name']] = '';
                    continue;
                }
                
                $is_empty = false;

                if ($fdict[$file_name]['zip']) {
                    $ret = $fdict[$file_name]['zip']->getFromIndex($fdict[$file_name]['index']);
                    if ($ret === false) {
                        UOJResponse::page406("{$fdict[$file_name]['zip_name']} {$invalid_zip_msg}");
                    }
                    $up_content[$req['name']] = $ret;
                } else {
                    $up_content[$req['name']] = file_get_contents($files[$fdict[$file_name]['name']]['tmp_name']);
                }
            }

            if ($is_empty) {
                UOJResponse::page406('未上传任何题目要求的文件');
            }

            try {
                $this->submission_archive = UOJSubmissionArchive::create($zip_file_name_gen);
            } catch (UOJException $e) {
                UOJResponse::page406($e->getMessage());
            }

            try {
                foreach ($requirement as $req) {
                    $this->submission_archive->addFromString($req, $up_content[$req['name']]);
                }
            } catch (UOJException $e) {
                $this->submission_archive->close();
                $this->submission_archive->unlink();
                UOJResponse::page406($e->getMessage());
            }
            
            $this->submission_archive->close();
            
            $handle($this->submission_archive);
        };
    }
}
