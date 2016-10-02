function blog_editor_init(name, editor_config) {
	if (editor_config === undefined) {
		editor_config = {};
	}
	
	editor_config = $.extend({
		type: 'blog'
	}, editor_config);
	
	var input_title = $("#input-" + name + "_title");
	var input_tags = $("#input-" + name + "_tags");
	var input_content_md = $("#input-" + name + "_content_md");
	var input_is_hidden = $("#input-" + name + "_is_hidden");
	var this_form = input_content_md[0].form;
	
	var is_saved;
	var last_save_done = true;
	
	// init buttons
	var save_btn = $('<button type="button" class="btn btn-sm"></button>');
	var preview_btn = $('<button type="button" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-eye-open"></span></button>');
	var bold_btn = $('<button type="button" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-bold"></span></button>');
	var italic_btn = $('<button type="button" class="btn btn-default btn-sm"><span class="glyphicon glyphicon-italic"></span></button>');
	
	save_btn.tooltip({ container: 'body', title: '保存 (Ctrl-S)' });
	preview_btn.tooltip({ container: 'body', title: '预览 (Ctrl-D)' 	});
	bold_btn.tooltip({ container: 'body', title: '粗体 (Ctrl-B)' });
	italic_btn.tooltip({ container: 'body', title: '斜体 (Ctrl-I)' });
	
	var all_btn = [save_btn, preview_btn, bold_btn, italic_btn];
	
	// init toolbar
	var toolbar = $('<div class="btn-toolbar"></div>');
	toolbar.append($('<div class="btn-group"></div>')
		.append(save_btn)
		.append(preview_btn)
	);
	toolbar.append($('<div class="btn-group"></div>')
		.append(bold_btn)
		.append(italic_btn)
	);
	
	function set_saved(val) {
		is_saved = val;
		if (val) {
			save_btn.removeClass('btn-warning');
			save_btn.addClass('btn-success');
			save_btn.html('<span class="glyphicon glyphicon-saved"></span>');
			before_window_unload_message = null;
		} else {
			save_btn.removeClass('btn-success');
			save_btn.addClass('btn-warning');
			save_btn.html('<span class="glyphicon glyphicon-save"></span>');
			before_window_unload_message = '您所编辑的内容尚未保存';
		}
	}
	function set_preview_status(status) {
		// 0: normal
		// 1: loading
		// 2: loaded
		if (status == 0) {
			preview_btn.removeClass('active');
			for (var i = 0; i < all_btn.length; i++) {
				if (all_btn[i] != preview_btn) {
					all_btn[i].prop('disabled', false);
				}
			}
		} else if (status == 1) {
			for (var i = 0; i < all_btn.length; i++) {
				if (all_btn[i] != preview_btn) {
					all_btn[i].prop('disabled', true);
				}
			}
			preview_btn.addClass('active');
		}
	}
	
	set_saved(true);
	
	// init codemirror
	input_content_md.wrap('<div class="blog-content-md-editor"></div>');
	var blog_contend_md_editor = input_content_md.parent();
	input_content_md.before($('<div class="blog-content-md-editor-toolbar"></div>')
		.append(toolbar)
	);
	input_content_md.wrap('<div class="blog-content-md-editor-in"></div>');
	
	var codeeditor;
	if (editor_config.type == 'blog') {
		codeeditor = CodeMirror.fromTextArea(input_content_md[0], {
			mode: 'gfm',
			lineNumbers: true,
			matchBrackets: true,
			lineWrapping: true,
			styleActiveLine: true,
			theme: 'default'
		});
	} else if (editor_config.type == 'slide') {
		codeeditor = CodeMirror.fromTextArea(input_content_md[0], {
			mode: 'plain',
			lineNumbers: true,
			matchBrackets: true,
			lineWrapping: true,
			styleActiveLine: true,
			theme: 'default'
		});
	}
	
	function preview(html) {
		var iframe = $('<iframe frameborder="0"></iframe>');
		blog_contend_md_editor.append(
			$('<div class="blog-content-md-editor-preview" style="display: none;"></div>')
				.append(iframe)
		);
		var iframe_document = iframe[0].contentWindow.document;
		iframe_document.open();
		iframe_document.write(html);
		iframe_document.close();
		$(iframe_document).bind('keydown', 'ctrl+d', function() {
			preview_btn.click();
			return false;
		});
		
		blog_contend_md_editor.find('.blog-content-md-editor-in').slideUp('fast');
		blog_contend_md_editor.find('.blog-content-md-editor-preview').slideDown('fast', function() {
			set_preview_status(2);
			iframe.focus(); 
			iframe.find('body').focus();
		});
	}
	function save(config) {
		if (config == undefined) {
			config = {};
		}
		config = $.extend({
			need_preview: false,
			fail: function() {
			},
			done: function() {
			}
		}, config);
		
		if (!last_save_done) {
			config.fail();
			config.done();
			return;
		}
		last_save_done = false;
		
		if (config.need_preview) {
			set_preview_status(1);
		}
		
		var post_data = {};
		post_data["save-" + name] = '';
		$($(this_form).serializeArray()).each(function() {
			post_data[this["name"]] = this["value"];
		});
		if (config.need_preview) {
			post_data['need_preview'] = 'on';
		}
		
		$.ajax({
			type : 'POST',
			data : post_data,
			url : window.location.href,
			success : function(data) {
				try {
					data = JSON.parse(data)
				} catch (e) {
					alert(data);
					if (config.need_preview) {
						set_preview_status(0);
					}
					config.fail();
					return;
				}
				var ok = true;
				$(['title', 'content_md', 'tags']).each(function() {
					ok &= showErrorHelp(name + '_' + this, data[this]);
				});
				if (data.extra !== undefined) {
					alert(data.extra);
					ok = false;
				}
				if (!ok) {
					if (config.need_preview) {
						set_preview_status(0);
					}
					config.fail();
					return;
				}
				
				set_saved(true);
				
				if (config.need_preview) {
					preview(data.html);
				}
				
				if (data.blog_write_url) {
					window.history.replaceState({}, document.title, data.blog_write_url);
				}
				if (data.blog_url) {
					$('#a-' + name + '_view_blog').attr('href', data.blog_url).show();
				}
			}
		}).fail(function() {
			if (config.need_preview) {
				set_preview_status(0);
			}
			config.fail();
		}).always(function() {
			last_save_done = true;
			config.done();
		});
	}
	function add_around(sl, sr) {
		codeeditor.replaceSelection(sl + codeeditor.getSelection() + sr);
	}
	
	// event
	codeeditor.on('change', function() {
		codeeditor.save();
		set_saved(false);
	});
	$.merge(input_title, input_tags).on('input', function() {
		set_saved(false);
	});
	save_btn.click(function() {
		save();
	});
	preview_btn.click(function() {
		if (preview_btn.hasClass('active')) {
			set_preview_status(0);
			blog_contend_md_editor.find('.blog-content-md-editor-in').slideDown('fast');
			blog_contend_md_editor.find('.blog-content-md-editor-preview').slideUp('fast', function() {
				$(this).remove();
			});
			codeeditor.focus();
		} else {
			save({need_preview: true});
		}
	});
	bold_btn.click(function() {
		add_around("**", "**");
		codeeditor.focus();
	});
	italic_btn.click(function() {
		add_around("*", "*");
		codeeditor.focus();
	});
	input_is_hidden.on('switchChange.bootstrapSwitch', function(e, state) {
		var ok = true;
		if (!state && !confirm("你确定要公开吗？")) {
			ok = false;
		}
		if (!ok) {
			input_is_hidden.bootstrapSwitch('toggleState', true);
		} else {
			input_is_hidden.bootstrapSwitch('readonly', true);
			var succ = true;
			save({
				fail: function() {
					succ = false;
				},
				done: function() {					
					input_is_hidden.bootstrapSwitch('readonly', false);
					if (!succ) {
						input_is_hidden.bootstrapSwitch('toggleState', true);
					}
				}
			});
		}
	});
	
	// init hot keys
	codeeditor.setOption("extraKeys", {
		"Ctrl-S": function(cm) {
			save_btn.click();
		},
		"Ctrl-B": function(cm) {
			bold_btn.click();
		},
		"Ctrl-D": function(cm) {
			preview_btn.click();
		},
		"Ctrl-I": function(cm) {
			italic_btn.click();
		}
	});
	$(document).bind('keydown', 'ctrl+d', function() {
		preview_btn.click();
		return false;
	});
	$.merge(input_title, input_tags).bind('keydown', 'ctrl+s', function() {
		save_btn.click();
		return false;
	});
	
	if (this_form) {
		$(this_form).submit(function() {
			before_window_unload_message = null;
		});
	}
}
