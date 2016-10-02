(function() {
	$.fn.slide_editor = function() {
		return this.each(function() {
			var name = this.id;
			var this_form = this.form;
			
			$(this).replaceWith($('<div id="' + name + '-slide-editor" class="slide-editor"></div>')
				.append($(this).clone())
			);

			var codeeditor = CodeMirror.fromTextArea($('#' + name)[0], {
				mode: 'plain',
				lineNumbers: true,
				matchBrackets: true,
				lineWrapping: true,
				styleActiveLine: true,
				theme: 'default'
			});
			
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
		});
	};
})()
