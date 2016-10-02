(function() {
	$.fn.markdown_editor = function() {
		return this.each(function() {
			var name = this.id;
			var this_form = this.form;
			
			$(this).replaceWith($('<div id="' + name + '-markdown-editor" class="markdown-editor"></div>')
				.append(
					$('<div id="' + name + '-markdown-editor-in" class="markdown-editor-in"></div>')
						.append($(this).clone())
				)
				.append(
					$('<div id="' + name + '-markdown-editor-out" class="markdown-editor-out"></div>')
				)
			);
	
			var needRerender = false;
	
			var update = function(e){
				var val = e.getValue();
				setOutput(val);
				needRerender = true;
			}
	
			var autoRerender = function() {
				if (needRerender) {
					needRerender = false;
					$('#' + name + '-markdown-editor-out').each(function() {
						sh_highlightDocument(this);
						MathJax.Hub.Queue(["Typeset", MathJax.Hub, this]);
					});
				}
				setTimeout(autoRerender, 500);
			}
	
			var setOutput = function(val){
				$('#' + name + '-markdown-editor-out').html(marked(val));
			}

			var codeeditor = CodeMirror.fromTextArea($('#' + name)[0], {
				mode: 'gfm',
				lineNumbers: true,
				matchBrackets: true,
				lineWrapping: true,
				styleActiveLine: true,
				theme: 'default'
			});
			
			codeeditor.on('change', update);
			
			if (this_form) {
				var changed = false;
				codeeditor.on('change', function() {
					before_window_unload_message = '您所编辑的内容尚未保存';
					changed = true;
				});
				$(this_form).submit(function() {
					if (changed) {
						before_window_unload_message = null;
					}
					changed = false;
				});
			}
	
			update(codeeditor);
			autoRerender();
		});
	};
})()
