<?php

class DropzoneForm {
	public static $default_dropzone_config = [
		'params' => [],
		'paramName' => 'file',
		'maxFiles' => 100,
		'dictDefaultMessage' => '想要上传文件？请把文件拖到这里',
		'addRemoveLinks' => false,
		'autoProcessQueue' => false,
		'parallelUploads' => 100,
		'uploadMultiple' => true
	];

	public string $name;
	public string $url;
	public ?string $succ_href;
	public array $hidden_data = [];
	public array $dropzone_config = [];
	public array $dropzone_config_direct = [];
	public array $submit_button_config = [];
	public string $submit_condition = <<<EOD
		(dz) => {
			let files = dz.getQueuedFiles();
			if (files.length < 1) {
				dz.emit('errormultiple', files, '请提交至少一个文件');
				return false;
			}
			return true;
		}
		EOD;
	public string $introduction = '';
	public $handler;
	
	public $extra_validators = [];
	
	function __construct(string $name, array $cfg = [], array $cfg_direct = []) {
		requireLib('dropzone');
		
		$this->name = $name;
		$this->url = UOJContext::requestURI();
		$this->succ_href = $this->url;
		$this->dropzone_config = $cfg + self::$default_dropzone_config;
		$this->dropzone_config['params'] += [
			"submit-{$this->name}" => $this->name,
			'_token' => crsf_token()
		];
		$this->dropzone_config_direct = $cfg_direct;
		$this->submit_button_config += [
			'text' => UOJLocale::get('submit')
		];
		$this->extra_validators[] = function() {
			if ($this->dropzone_config['maxFiles'] !== null && count($_FILES) > $this->dropzone_config['maxFiles']) {
				return '上传出错：你上传了太多文件了';
			}
			return '';
		};
	}

	public function formID(): string {
		return "form-{$this->name}";
	}

	public function divDropzoneID() {
		return "{$this->formID()}-div-dropzone";
	}

	public function helpBlockID() {
		return "{$this->formID()}-help-block";
	}

	public function getFile() {
		assert(!$this->dropzone_config['uploadMultiple']);
		return $_FILES[$this->dropzone_config['paramName']];
	}

	public function getFiles() {
		assert($this->dropzone_config['uploadMultiple']);
		if (!is_array($_FILES[$this->dropzone_config['paramName']]['name'])) {
			UOJResponse::page406('好像上传出了点问题，再试试？');
		}
		$n = count($_FILES[$this->dropzone_config['paramName']]['name']);
		$fields = ['name', 'type', 'tmp_name', 'error', 'size'];
		$files = [];
		for ($i = 0; $i < $n; $i++) {
			$file = [];
			foreach ($fields as $field) {
				if (!isset($_FILES[$this->dropzone_config['paramName']][$field][$i])) {
					UOJResponse::page406('好像上传出了点问题，再试试？');
				}
				$file[$field] = $_FILES[$this->dropzone_config['paramName']][$field][$i];
			}
			if (isset($files[$file['name']])) {
				UOJResponse::page406('上传的文件中出现了重复的文件名！');
			}
			if (!is_uploaded_file($file['tmp_name'])) {
				UOJResponse::page406('好像文件没有成功传过来，再试试？');
			}
			$files[$file['name']] = $file;
		}
		return $files;
	}
	
	public function runAtServer() {
		if (isset($_POST["submit-{$this->name}"]) && !empty($_FILES)) {
			foreach ($this->extra_validators as $val) {
				$err = $val();
				$err === '' || UOJResponse::message($err);
			}
			($this->handler)($this);
		}
	}
	public function printHTML() {
		uojIncludeView('dropzone-form', ['form' => $this]);
	}
}
