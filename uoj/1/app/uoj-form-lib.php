<?php
	class UOJForm {
		public $form_name;
		public $succ_href;
		public $no_submit = false;
		public $ctrl_enter_submit = false;
		public $extra_validator = null;
		public $is_big = false;
		public $has_file = false;
		public $ajax_submit_js = null;
		public $run_at_server_handler = array();
		private $data = array();
		private $vdata = array();
		private $main_html = '';
		public $max_post_size = 15728640; // 15M
		
		public $handle;
		
		public $submit_button_config = array();
		
		public function __construct($form_name) {
			$this->form_name = $form_name;
			$this->succ_href = $_SERVER['REQUEST_URI'];
			$this->handle = function(&$vdata){};
			
			$this->run_at_server_handler["check-{$this->form_name}"] = function() {
				die(json_encode($this->validateAtServer()));
			};
			$this->run_at_server_handler["submit-{$this->form_name}"] = function() {
				if ($this->no_submit) {
					become404Page();
				}
				foreach ($this->data as $field) {
					if (!isset($field['no_val']) && !isset($_POST[$field['name']])) {
						becomeMsgPage('The form is incomplete.');
					}
				}
				
				if (UOJContext::requestMethod() == 'POST') {
					$len = UOJContext::contentLength();
					if ($len === null) {
						become403Page();
					} elseif ($len > $this->max_post_size) {
						becomeMsgPage('The form is too large.');
					}
				}
				
				crsf_defend();
				$errors = $this->validateAtServer();
				if ($errors) {
					$err_str = '';
					foreach ($errors as $name => $err) {
						$esc_err = htmlspecialchars($err);
						$err_str .= "$name: $esc_err<br />";
					}
			 		becomeMsgPage($err_str);
				}
				$fun = $this->handle;
				$fun($this->vdata);
				
				if ($this->succ_href !== 'none') {
					header("Location: {$this->succ_href}");
				}
				die();
			};
		}

		public function setAjaxSubmit($js) {
			$GLOBALS['REQUIRE_LIB']['jquery.form'] = '';
			$this->ajax_submit_js = $js;
		}
		
		public function add($name, $html, $validator_php, $validator_js) {
			$this->main_html .= $html;
			$this->data[] = array(
				'name' => $name,
				'validator_php' => $validator_php,
				'validator_js' => $validator_js);
		}
		public function appendHTML($html) {
			$this->main_html .= $html;
		}
		
		public function addNoVal($name, $html) {
			$this->main_html .= $html;
			$this->data[] = array(
				'name' => $name,
				'validator_js' => 'always_ok',
				'no_val' => '');
		}
		
		public function addHidden($name, $default_value, $validator_php, $validator_js) {
			$default_value = htmlspecialchars($default_value);
			$html = <<<EOD
<input type="hidden" name="$name" id="input-$name" value="$default_value" />
EOD;
			$this->add($name, $html, $validator_php, $validator_js);
		}
		
		public function addInput($name, $type, $label_text, $default_value, $validator_php, $validator_js) {
			$default_value = htmlspecialchars($default_value);
			$html = <<<EOD
<div id="div-$name" class="form-group">
	<label for="input-$name" class="col-sm-2 control-label">$label_text</label>
	<div class="col-sm-3">
		<input type="$type" class="form-control" name="$name" id="input-$name" value="$default_value" />
		<span class="help-block" id="help-$name"></span>
	</div>
</div>
EOD;
			$this->add($name, $html, $validator_php, $validator_js);
		}
		public function addSelect($name, $options, $label_text, $default_value) {
			$default_value = htmlspecialchars($default_value);
			$html = <<<EOD
<div id="div-$name" class="form-group">
	<label for="input-$name" class="col-sm-2 control-label">$label_text</label>
	<div class="col-sm-3">
		<select class="form-control" id="input-content" name="$name">

EOD;
			foreach ($options as $opt_name => $opt_label) {
				if ($opt_name != $default_value) {
					$html .= <<<EOD
			<option value="$opt_name">$opt_label</option>

EOD;
				} else {
					$html .= <<<EOD
			<option value="$opt_name" selected="selected">$opt_label</option>

EOD;
				}
			}
			$html .= <<<EOD
		</select>
	</div>
</div>
EOD;
			$this->add($name, $html,
				function($opt) use ($options) {
					return isset($options[$opt]) ? '' : "无效选项";
				},
				null
			);
		}
		
		public function addVInput($name, $type, $label_text, $default_value, $validator_php, $validator_js) {
			$html = HTML::div_vinput($name, $type, $label_text, $default_value);
			$this->add($name, $html, $validator_php, $validator_js);
		}
		public function addVSelect($name, $options, $label_text, $default_value) {
			$default_value = htmlspecialchars($default_value);
			$html = <<<EOD
<div id="div-$name">
	<label for="input-$name" class="control-label">$label_text</label>
	<select class="form-control" id="input-{$name}" name="$name">

EOD;
			foreach ($options as $opt_name => $opt_label) {
				if ($opt_name != $default_value) {
					$html .= <<<EOD
			<option value="$opt_name">$opt_label</option>

EOD;
				} else {
					$html .= <<<EOD
			<option value="$opt_name" selected="selected">$opt_label</option>

EOD;
				}
			}
			$html .= <<<EOD
	</select>
</div>
EOD;
			$this->add($name, $html,
				function($opt) use ($options) {
					return isset($options[$opt]) ? '' : "无效选项";
				},
				null
			);
		}
		
		public function addTextArea($name, $label_text, $default_value, $validator_php, $validator_js) {
			$default_value = htmlspecialchars($default_value);
			$this->is_big = true;
			$html = <<<EOD
<div id="div-$name" class="form-group">
	<label for="input-$name" class="col-sm-2 control-label">$label_text</label>
	<div class="col-sm-10">
		<textarea class="form-control" name="$name" id="input-$name">$default_value</textarea>
		<span class="help-block" id="help-$name"></span>
	</div>
</div>
EOD;
			$this->add($name, $html, $validator_php, $validator_js);
		}
		public function addVTextArea($name, $label_text, $default_value, $validator_php, $validator_js) {
			$default_value = htmlspecialchars($default_value);
			$this->is_big = true;
			$html = <<<EOD
<div id="div-$name">
	<label for="input-$name" class="control-label">$label_text</label>
	<textarea class="form-control" name="$name" id="input-$name">$default_value</textarea>
	<span class="help-block" id="help-$name"></span>
</div>
EOD;
			$this->add($name, $html, $validator_php, $validator_js);
		}
		public function addCheckBox($name, $label_text, $default_value) {
			$default_value = htmlspecialchars($default_value);
			$status = $default_value ? 'checked="checked" ' : '';
			$html = <<<EOD
<div class="checkbox">
	<label for="input-$name"><input type="checkbox" id="input-$name" name="$name" $status/> $label_text</label>
</div>
EOD;
			$this->addNoVal($name, $html);
		}
		public function addCKEditor($name, $label_text, $default_value, $validator_php, $validator_js) {
			$default_value = htmlspecialchars($default_value);
			global $REQUIRE_LIB;
			$REQUIRE_LIB['ckeditor'] = '';
			
			$this->is_big = true;
			
			$html = <<<EOD
<div id="div-$name">
	<label for="input-$name" class="control-label">$label_text</label>
	<textarea class="ckeditor" name="$name" id="input-$name">$default_value</textarea>
	<span class="help-block" id="help-$name"></span>
</div>
EOD;
			$this->add($name, $html, $validator_php, $validator_js);
		}
		
		public function addBlogEditor(UOJBlogEditor $editor) {
			global $REQUIRE_LIB;
			$REQUIRE_LIB['blog-editor'] = '';
			
			$this->is_big = true;
			
			$name = $editor->name;
			
			$this->addVInput("{$name}_title", 'text', '标题', $editor->cur_data['title'],
				function ($title) use($editor) {
					return $editor->validateTitle();
				},
				null
			);
			
			$content_md_html = HTML::div_vtextarea("{$name}_content_md", '内容', $editor->cur_data['content_md']);
			
			$this->add("{$name}_content_md", $content_md_html,
				function ($content_md) use($editor) {
					return $editor->validateContentMd();
				},
				'always_ok'
			);
		
			$this->appendHTML(<<<EOD
<script type="text/javascript">blog_editor_init("$name");</script>
EOD
			);
			
			$this->run_at_server_handler["save-{$name}"] = function() use($name, $editor) {
				if ($this->no_submit) {
					become404Page();
				}
				foreach (array("{$name}_title", "{$name}_content_md") as $field_name) {
					if (!isset($_POST[$field_name])) {
						becomeMsgPage('The form is incomplete.');
					}
				}
				crsf_defend();
				$errors = $this->validateAtServer();
				if (!$errors) {
					$editor->handleSave();
				}
				die(json_encode($errors));
			};
		}
		public function addSlideEditor($name, $label_text, $default_value, $validator_php, $validator_js) {
			$default_value = htmlspecialchars($default_value);
			global $REQUIRE_LIB;
			$REQUIRE_LIB['slide-editor'] = '';
			
			$this->is_big = true;
			
			$html = <<<EOD
<div id="div-$name">
	<label for="input-$name" class="control-label">$label_text</label>
	<textarea name="$name" id="input-$name">$default_value</textarea>
	<span class="help-block" id="help-$name"></span>
</div>
<script type="text/javascript">
$('#input-$name').slide_editor();
</script>
EOD;
			$this->add($name, $html, $validator_php, $validator_js);
		}

		static public function uploadedFileTmpName($name) {
			if (isset($_FILES[$name]) && is_uploaded_file($_FILES[$name]['tmp_name'])) {
				return $_FILES[$name]['tmp_name'];
			} else {
				return null;
			}
		}
		
		public function addSourceCodeInput($name, $text, $languages) {
			$this->add("{$name}_upload_type", '',
				function($type, &$vdata) use($name) {
					if ($type == 'editor') {
						if (!isset($_POST["{$name}_editor"])) {
							return '你在干啥……怎么什么都没交过来……？';
						}
					} elseif ($type == 'file') {
					} else {
						return '……居然既不是用编辑器上传也不是用文件上传的……= =……';
					}
				},
				'always_ok'
			);
			$this->addNoVal("{$name}_editor", '');
			$this->addNoVal("{$name}_file", '');
			$this->add("{$name}_language", '',
				function($lang) use ($languages) {
					if (!in_array($lang, $languages)) {
						return '该语言不被支持';
					}
					return '';
				},
				'always_ok'
			);

			$preferred_language = Cookie::get('uoj_preferred_language');
			if ($preferred_language == null || !in_array($preferred_language, $languages)) {
				$preferred_language = $languages[0];
			}
			
			$langs_options_str = '';
			foreach ($languages as $lang) {
				$langs_options_str .= "<option";
				if ($lang == $preferred_language) {
					$langs_options_str .= ' selected="selected"';
				}
				$langs_options_str .= ">$lang</option>";
			}
			$langs_options_json = json_encode($langs_options_str);
			$this->appendHTML(<<<EOD
<div class="form-group" id="form-group-$name"></div>
<script type="text/javascript">
$('#form-group-$name').source_code_form_group('$name', '$text', $langs_options_json);
</script>
EOD
			);
			
			$this->is_big = true;
			$this->has_file = true;
		}
		public function addTextFileInput($name, $text) {
			$this->add("{$name}_upload_type", '',
				function($type, &$vdata) use($name) {
					if ($type == 'editor') {
						if (!isset($_POST["{$name}_editor"])) {
							return '你在干啥……怎么什么都没交过来……？';
						}
					} elseif ($type == 'file') {
					} else {
						return '……居然既不是用编辑器上传也不是用文件上传的……= =……';
					}
				},
				'always_ok'
			);
			$this->addNoVal("{$name}_editor", '');
			$this->addNoVal("{$name}_file", '');

			$this->appendHTML(<<<EOD
<div class="form-group" id="form-group-$name"></div>
<script type="text/javascript">
$('#form-group-$name').text_file_form_group('$name', '$text');
</script>
EOD
		);
			
			$this->is_big = true;
			$this->has_file = true;
		}
		
		public function printHTML() {
			$form_entype_str = $this->is_big ? ' enctype="multipart/form-data"' : '';
			echo '<form action="', $_SERVER['REQUEST_URI'], '" method="post" class="form-horizontal" id="form-', $this->form_name, '"', $form_entype_str, '>';
			echo HTML::hiddenToken();
			echo $this->main_html;
			
			if (!$this->no_submit) {
				if (!isset($this->submit_button_config['align'])) {
					$this->submit_button_config['align'] = 'center';
				}
				if (!isset($this->submit_button_config['text'])) {
					$this->submit_button_config['text'] = UOJLocale::get('submit');
				}
				if (!isset($this->submit_button_config['class_str'])) {
					$this->submit_button_config['class_str'] = 'btn btn-default';
				}
				if ($this->submit_button_config['align'] == 'offset') {
					echo '<div class="form-group">';
					echo '<div class="col-sm-offset-2 col-sm-3">';
				} else {
					echo '<div class="text-', $this->submit_button_config['align'], '">';
				}
				echo '<button type="submit" id="button-submit-', $this->form_name, '" name="submit-', $this->form_name, '" value="', $this->form_name, '" class="', $this->submit_button_config['class_str'], '">', $this->submit_button_config['text'], '</button>';
				if ($this->submit_button_config['align'] == 'offset') {
					echo '</div>';
				}
				echo '</div>';
			}
			
			echo '</form>';
			
			if ($this->no_submit) {
				return;
			}
			
			echo <<<EOD
<script type="text/javascript">
$(document).ready(function() {

EOD;
			if ($this->ctrl_enter_submit) {
				echo <<<EOD
	$('#form-{$this->form_name}').keydown(function(e) {
		if (e.keyCode == 13 && e.ctrlKey) {
			$('#button-submit-{$this->form_name}').click();
		}
	});

EOD;
			}
			echo <<<EOD
	$('#form-{$this->form_name}').submit(function(e) {
		var ok = true;

EOD;
			$need_ajax = false;
			if ($this->extra_validator) {
				$need_ajax = true;
			}
			foreach ($this->data as $field) {
				if ($field['validator_js'] != null) {
					if ($field['validator_js'] != 'always_ok') {
						echo <<<EOD
		var ${field['name']}_err = (${field['validator_js']})($('#input-${field['name']}').val());

EOD;
					}
				} else {
					$need_ajax = true;
				}
			}
			
			if ($need_ajax) {
				echo <<<EOD
		var post_data = {};

EOD;
				foreach ($this->data as $field) {
					if ($field['validator_js'] == null) {
						echo <<<EOD
		var ${field['name']}_err = 'Unknown error';
		post_data.${field['name']} = $('#input-${field['name']}').val();

EOD;
					}
				}
				echo <<<EOD
		if (post_data != {}) {
			post_data['check-{$this->form_name}'] = "";
			$.ajax({
				url : '${_SERVER['REQUEST_URI']}',
				type : 'POST',
				dataType : 'json',
				async : false,

				data : post_data,
				success : function(data) {

EOD;
				foreach ($this->data as $field) {
					if ($field['validator_js'] == null) {
						echo <<<EOD
					${field['name']}_err = data.${field['name']};

EOD;
					}
				}
				echo <<<EOD
					if (data.extra != undefined) {
						alert(data.extra);
						ok = false;
					}

EOD;
				echo <<<EOD
				}
			});
		}

EOD;
			}
		
			foreach ($this->data as $field) {
				if ($field['validator_js'] != 'always_ok') {
					echo <<<EOD
		if (${field['name']}_err) {
			$('#div-${field['name']}').addClass('has-error');
			$('#help-${field['name']}').text(${field['name']}_err);
			ok = false;
		} else {
			$('#div-${field['name']}').removeClass('has-error');
			$('#help-${field['name']}').text('');
		}

EOD;
				}
			}
			
			if (isset($this->submit_button_config['smart_confirm'])) {
				$this->submit_button_config['confirm_text'] = '你真的要' . $this->submit_button_config['text'] . '吗？';
			}
			if (isset($this->submit_button_config['confirm_text'])) {
				echo <<<EOD
		if (!confirm('{$this->submit_button_config['confirm_text']}')) {
			ok = false;
		}

EOD;
			}
			if ($this->has_file) {
				echo <<<EOD
		$(this).find("input[type='file']").each(function() {
			for (var i = 0; i < this.files.length; i++) {
				if (this.files[i].size > 10 * 1024 * 1024) {
					$('#div-' + $(this).attr('name')).addClass('has-error');
					$('#help-' + $(this).attr('name')).text('文件大小不能超过10M');
					ok = false;
				} else {
					$('#div-' + $(this).attr('name')).removeClass('has-error');
					$('#help-' + $(this).attr('name')).text('');
				}
			}
		});

EOD;
			}

			if ($this->ajax_submit_js !== null) {
				echo <<<EOD
		e.preventDefault();
		if (ok) {
			$(this).ajaxSubmit({
				beforeSubmit: function(formData) {
					formData.push({name: 'submit-{$this->form_name}', value: '{$this->form_name}', type: 'submit'});
				},
				success : {$this->ajax_submit_js}
			});
		}

EOD;
			} else {
				echo <<<EOD
		return ok;

EOD;
			}
			echo <<<EOD
	});
});
</script>
EOD;
		}
		
		private function validateAtServer() {
			$errors = array();
			if ($this->extra_validator) {
				$fun = $this->extra_validator;
				$err = $fun();
				if ($err) {
					$errors['extra'] = $err;
				}
			}
			foreach ($this->data as $field) {
				if (!isset($field['no_val']) && isset($_POST[$field['name']])) {
					$fun = $field['validator_php'];
					$err = $fun($_POST[$field['name']], $this->vdata);
					if ($err) {
						$errors[$field['name']] = $err;
					}
				}
			}
			return $errors;
		}
		
		public function runAtServer() {
			foreach ($this->run_at_server_handler as $type => $handler) {
				if (isset($_POST[$type])) {
					$handler();
				}
			}
		}
	}
	
	function newAddDelCmdForm($form_name, $validate, $handle, $final = null) {
		$form = new UOJForm($form_name);
		$form->addTextArea(
			$form_name . '_cmds', '命令', '',
			function($str, &$vdata) use($validate) {
				$cmds = array();
				foreach (explode("\n", $str) as $line_id => $raw_line) {
					$line = trim($raw_line);
					if ($line == '') {
						continue;
					}
					if ($line[0] != '+' && $line[0] != '-') {
						return '第' . ($line_id + 1) . '行：格式错误';
					}
					$obj = trim(substr($line, 1));
					
					if ($err = $validate($obj)) {
						return '第' . ($line_id + 1) . '行：' . $err;
					}
					$cmds[] = array('type' => $line[0], 'obj' => $obj);
				}
				$vdata['cmds'] = $cmds;
				return '';
			},
			null
		);
		if (!isset($final)) {
			$form->handle = function(&$vdata) use($handle) {
				foreach ($vdata['cmds'] as $cmd) {
					$handle($cmd['type'], $cmd['obj']);
				}
			};
		} else {
			$form->handle = function(&$vdata) use($handle, $final) {
				foreach ($vdata['cmds'] as $cmd) {
					$handle($cmd['type'], $cmd['obj']);
				}
				$final();
			};
		}
		return $form;
	}
	
	function newSubmissionForm($form_name, $requirement, $zip_file_name_gen, $handle) {
		$form = new UOJForm($form_name);
		foreach ($requirement as $req) {
			if ($req['type'] == "source code") {
				$languages = isset($req['languages']) ? $req['languages'] : $GLOBALS['uojSupportedLanguages'];
				$form->addSourceCodeInput("{$form_name}_{$req['name']}", UOJLocale::get('problems::source code').':'.$req['name'], $languages);
			} elseif ($req['type'] == "text") {
					$form->addTextFileInput("{$form_name}_{$req['name']}", UOJLocale::get('problems::text file').':'.$req['file_name']);
			}
		}
		
		$form->handle = function(&$vdata) use($form_name, $requirement, $zip_file_name_gen, $handle) {
			global $myUser;
			
			if ($myUser == null) {
				redirectToLogin();
			}
			
			$tot_size = 0;
			$zip_file_name = $zip_file_name_gen();
			
			$zip_file = new ZipArchive();
			if ($zip_file->open(UOJContext::storagePath().$zip_file_name, ZipArchive::CREATE) !== true) {
				becomeMsgPage('提交失败');
			}
			
			$content = array();
			$content['file_name'] = $zip_file_name;
			$content['config'] = array();
			foreach ($requirement as $req) {
				if ($req['type'] == "source code") {
					$content['config'][] = array("{$req['name']}_language", $_POST["{$form_name}_{$req['name']}_language"]);
				}
			}
			
			foreach ($requirement as $req) {
				if ($_POST["{$form_name}_{$req['name']}_upload_type"] == 'editor') {
					$zip_file->addFromString($req['file_name'], $_POST["{$form_name}_{$req['name']}_editor"]);
				} else {
					$tmp_name = UOJForm::uploadedFileTmpName("{$form_name}_{$req['name']}_file");
					if ($tmp_name == null) {
						$zip_file->addFromString($req['file_name'], '');
					} else {
						$zip_file->addFile($tmp_name, $req['file_name']);
					}
				}
				$stat = $zip_file->statName($req['file_name']);
				
				if ($req['type'] == 'source code') {
					$max_size = isset($req['size']) ? (int)$req['size'] : 50;
					if ($stat['size'] > $max_size * 1024) {
						$zip_file->close();
						unlink(UOJContext::storagePath().$zip_file_name);
						becomeMsgPage("源代码长度不能超过 {$max_size}KB。");
					}
				}
				
				$tot_size += $stat['size'];
			}
			
			$zip_file->close();
			
			$handle($zip_file_name, $content, $tot_size);
		};
		return $form;
	}
	function newZipSubmissionForm($form_name, $requirement, $zip_file_name_gen, $handle) {
		$form = new UOJForm($form_name);
		$name = "zip_ans_{$form_name}";
		$text = UOJLocale::get('problems::zip file upload introduction', join(array_map(function($req){return $req['file_name'];}, $requirement), ', '));
		$browse_text = UOJLocale::get('browse');
		$html = <<<EOD
<div id="div-{$name}">
	<label for="input-{$name}">$text</label>
	<input type="file" id="input-{$name}" name="{$name}" style="display:none;" onchange="$('#input-{$name}_path').val($('#input-{$name}').val());" />
	<div class="input-group bot-buffer-md">
		<input id="input-{$name}_path" class="form-control" type="text" readonly="readonly" />
		<span class="input-group-btn">
			<button type="button" class="btn btn-primary" style="width:100px; !important" onclick="$('#input-{$name}').click();"><span class="glyphicon glyphicon-folder-open"></span> $browse_text</button>
		</span>
	</div>
	<span class="help-block" id="help-{$name}"></span>
</div>
EOD;
		
		$form->addNoVal($name, $html);	
		$form->is_big = true;
		$form->has_file = true;
		
		$form->handle = function() use($name, $requirement, $zip_file_name_gen, $handle) {
			global $myUser;
			
			if ($myUser == null) {
				redirectToLogin();
			}
			
			if (!isset($_FILES[$name])) {
				becomeMsgPage('你在干啥……怎么什么都没交过来……？');
			} elseif (!is_uploaded_file($_FILES[$name]['tmp_name'])) {
				becomeMsgPage('上传出错，貌似你什么都没交过来……？');
			}
			
			$up_zip_file = new ZipArchive();
			if ($up_zip_file->open($_FILES[$name]['tmp_name']) !== true) {
				becomeMsgPage('不是合法的zip压缩文件');
			}
			
			$tot_size = 0;
			$zip_content = array();
			foreach ($requirement as $req) {
				$stat = $up_zip_file->statName($req['file_name']);
				if ($stat === false) {
					$zip_content[$req['name']] = '';
				} else {
					$tot_size += $stat['size'];
					if ($stat['size'] > 20 * 1024 * 1024) {
						becomeMsgPage("文件 {$req['file_name']} 实际大小过大。");
					}
					$ret = $up_zip_file->getFromName($req['file_name']);
					if ($ret === false) {
						$zip_content[$req['name']] = '';
					} else {
						$zip_content[$req['name']] = $ret;
					}
				}
			}
			$up_zip_file->close();
			
			$zip_file_name = $zip_file_name_gen();
			
			$zip_file = new ZipArchive();
			if ($zip_file->open(UOJContext::storagePath().$zip_file_name, ZipArchive::CREATE) !== true) {
				becomeMsgPage('提交失败');
			}
			
			foreach ($requirement as $req) {
 				$zip_file->addFromString($req['file_name'], $zip_content[$req['name']]);
			}
			$zip_file->close();
			
			$content = array();
			$content['file_name'] = $zip_file_name;
			$content['config'] = array();
			
			$handle($zip_file_name, $content, $tot_size);
		};
		return $form;
	}
?>
