<?php

class UOJForm {
	public $form_name;
	public $succ_href;
	public $back_href = null;
	public $no_submit = false;
	public $ctrl_enter_submit = false;
	public $extra_validator = null;
	public $is_big = false;
	public $has_file = false;
	public $ajax_submit_js = null;
	public $error_on_incomplete_form = true;
	public $prevent_multiple_submit = true;
	public $run_at_server_handler = [];

    /**
     * @var array $data a list of input fields in the form
     */
	private array $data = [];

    /**
     * @var array $vdata an associative array that can be used to store data in the validation stage
     */
	private array $vdata = [];

    /**
     * @var string $main_html the main html of the form, which doesn't include <form>, js scripts, etc.
     */
	private string $main_html = '';

	public $max_post_size = 15728640; // 15M
	public $max_file_size_mb = 10; // 10M
	
	public $handle;
	
	public $submit_button_config = [];
	public $control_label_config = ['class' => 'col-sm-2'];
	public $input_config = ['class' => 'col-sm-3'];
	public $textarea_config = ['class' => 'col-sm-10'];
	
	public function __construct($form_name) {
		$this->form_name = $form_name;
		$this->succ_href = UOJContext::requestURI();
		$this->handle = function(&$vdata){};
		
		$this->run_at_server_handler["check-{$this->form_name}"] = function() {
			die(json_encode($this->validateAtServer()));
		};
		$this->run_at_server_handler["submit-{$this->form_name}"] = function() {
			if ($this->no_submit) {
				UOJResponse::page404();
			}
			if ($this->error_on_incomplete_form) {
				foreach ($this->data as $field) {
					if (!isset($field['no_val']) && !isset($_POST[$field['name']])) {
						UOJResponse::message('未正确上传完整数据。。重试一下或者联系管理员？');
					}
				}
			}

			if (UOJContext::requestMethod() == 'POST') {
				$len = UOJContext::contentLength();
				if ($len === null) {
					UOJResponse::page403();
				} elseif ($len > $this->max_post_size) {
					UOJResponse::message('传过来的数据太大了！');
				}
			}

			crsf_defend();
			$errors = $this->validateAtServer();
			if ($errors) {
				$err_str = '';
				foreach ($errors as $name => $err) {
					$esc_err = HTML::escape($err);
					$err_str .= "$name: $esc_err<br />";
				}
				UOJResponse::message($err_str);
			}
			$fun = $this->handle;
			$fun($this->vdata);
			
			if ($this->succ_href !== 'none') {
				redirectTo($this->succ_href);
			}
			die();
		};
	}

	public function setAjaxSubmit($js) {
		$GLOBALS['REQUIRE_LIB']['jquery.form'] = '';
		$this->ajax_submit_js = $js;
	}
	
    /**
     * add an input field
     * 
     * @param string $name name of the input field
     * @param string $html HTML for this input field
     * @param callable|null $validator_php a validate function to run at the server after user submits. 
     *        It will called as $val($_POST[$field['name']], $this->vdata, $field['name'])
     *        Expected return value:
     *        1. An array $ret (recommended): $ret['error'] will be reported as an error (required);
     *           $ret['store'] will be stored in $vdata[$field['name']] (optional).
     *        2. A non-empty error string
     *        3. Anything that can be considered false, meaning no error and do nothing
     * @param string|null $validator_js a validate function to run at the client before user submits.
     * 
     */
	public function add($name, $html, $validator_php, $validator_js) {
		$this->main_html .= $html;
		$this->data[] = [
			'name' => $name,
			'validator_php' => $validator_php,
			'validator_js' => $validator_js
        ];
	}

	public function appendHTML($html) {
		$this->main_html .= $html;
	}
	
	public function addNoVal($name, $html) {
		$this->main_html .= $html;
		$this->data[] = [
			'name' => $name,
			'validator_js' => 'always_ok',
			'no_val' => ''
        ];
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
			<label for="input-$name" class="{$this->control_label_config['class']} control-label">$label_text</label>
			<div class="{$this->input_config['class']}">
		EOD;

		$attr = is_array($type) ? $type : ['type' => $type];
		$attr += [
			'class' => 'form-control', 'name' => $name, 'id' => "input-$name",
			'value' => $default_value
		];
		$html .= HTML::empty_tag('input', $attr);
		$html .= <<<EOD
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
			<label for="input-$name" class="{$this->control_label_config['class']} control-label">$label_text</label>
			<div class="{$this->input_config['class']}">
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
			<label for="input-$name" class="{$this->control_label_config['class']} control-label">$label_text</label>
			<div class="{$this->textarea_config['class']}">
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
	
	static public function uploadedFileTmpName($name) {
		if (isset($_FILES[$name]) && is_uploaded_file($_FILES[$name]['tmp_name'])) {
			return $_FILES[$name]['tmp_name'];
		} else {
			return null;
		}
	}
	
	public function addSourceCodeInput($name, $text, $languages, $preferred_lang = null) {
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
				if (!isset($languages[$lang])) {
					return '该语言不被支持';
				}
				return '';
			},
			'always_ok'
		);

		if ($preferred_lang == null || !isset($languages[$preferred_lang])) {
			$preferred_lang = Cookie::get('uoj_preferred_language');
		}
		if ($preferred_lang == null || !isset($languages[$preferred_lang])) {
			$preferred_lang = UOJLang::$default_preferred_language;
		}
		
		$langs_options_str = '';
		foreach ($languages as $lang_code => $lang_display) {
			$langs_options_str .= '<option';
			$langs_options_str .= ' value="'.$lang_code.'"';
			if ($lang_code == $preferred_lang) {
				$langs_options_str .= ' selected="selected"';
			}
			$langs_options_str .= ">$lang_display</option>";
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

			if ($this->back_href !== null) {
				echo '<div class="btn-toolbar">';
			} 
			echo HTML::tag('button', [
				'type' => 'submit', 'id' => "button-submit-{$this->form_name}", 'name' => "submit-{$this->form_name}",
				'value' => $this->form_name, 'class' => $this->submit_button_config['class_str']
			], $this->submit_button_config['text']);
			if ($this->back_href !== null) {
				echo HTML::tag('a', [
					'class' => 'btn btn-default', 'href' => $this->back_href
				], '返回');
			}
			if ($this->back_href !== null) {
				echo '</div>';
			}

			if ($this->submit_button_config['align'] == 'offset') {
				echo '</div>';
			}
			echo '</div>';
		}
		
		echo '</form>';
		
		if ($this->no_submit) {
			return;
		}
		$this->printFormJS();
	}

	protected function printFormJS () {
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
							var {$field['name']}_err = ({$field['validator_js']})($('#input-{$field['name']}').val());

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
							var {$field['name']}_err = 'Unknown error';
							post_data.{$field['name']} = $('#input-{$field['name']}').val();

					EOD;
				}
			}
			echo <<<EOD
							post_data['check-{$this->form_name}'] = "";
							$.ajax({
								url : '{$_SERVER['REQUEST_URI']}',
								type : 'POST',
								dataType : 'json',
								async : false,

								data : post_data,
								success : function(data) {

					EOD;
			foreach ($this->data as $field) {
				if ($field['validator_js'] == null) {
					echo <<<EOD
									{$field['name']}_err = data.${field['name']};

					EOD;
				}
			}
			echo <<<EOD
									if (data.extra != undefined) {
										alert(data.extra);
										ok = false;
									}
								}
							});

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
									if (this.files[i].size > {$this->max_file_size_mb} * 1024 * 1024) {
										$('#div-' + $(this).attr('name')).addClass('has-error');
										$('#help-' + $(this).attr('name')).text('文件大小不能超过{$this->max_file_size_mb}M');
										ok = false;
									} else {
										$('#div-' + $(this).attr('name')).removeClass('has-error');
										$('#help-' + $(this).attr('name')).text('');
									}
								}
							});

					EOD;
		}

		
		if ($this->prevent_multiple_submit) {
			echo <<<EOD
							if (ok) {
								$("#button-submit-{$this->form_name}").addClass('disabled');
								$(this).submit(function () {
									return false;
								});
							}

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
		$errors = [];
		if ($this->extra_validator) {
			$fun = $this->extra_validator;
			$err = $fun();
			if ($err) {
				$errors['extra'] = $err;
			}
		}
		foreach ($this->data as $field) {
			if (!isset($field['no_val'])) {
				if ($this->error_on_incomplete_form && !isset($_POST[$field['name']])) {
					continue;
				}
				$fun = $field['validator_php'];
				$ret = $fun(UOJRequest::post($field['name']), $this->vdata, $field['name']);
				if (is_array($ret) && isset($ret['error'])) {
					$err = $ret['error'];
				} else {
					$err = $ret;
				}
				if ($err) {
					$errors[$field['name']] = $err;
				}
				if (is_array($ret) && isset($ret['store'])) {
					$this->vdata[$field['name']] = $ret['store'];
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
