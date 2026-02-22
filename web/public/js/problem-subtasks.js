(function() {
	function escapeHTML(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function parseIntOrDefault(val, defaultVal) {
		var n = parseInt(val, 10);
		return isNaN(n) ? defaultVal : n;
	}

	function readProblemConf($table) {
		var conf = {};
		var subtaskRows = [];
		var subtaskKeyRegex = /^subtask_(?:end|score|dependence|type|used_time_type)_\d+(?:_\d+)?$/;

		$table.find('tbody tr').each(function() {
			var $cells = $(this).children('td');
			if ($cells.length < 2) {
				return;
			}

			var key = $.trim($cells.eq(0).text());
			if (key === '') {
				return;
			}

			var $valCell = $cells.eq(1).clone();
			$valCell.find('.glyphicon').remove();
			var val = $.trim($valCell.text());

			conf[key] = val;
			if (subtaskKeyRegex.test(key)) {
				subtaskRows.push(this);
			}
		});

		return {
			conf: conf,
			subtaskRows: $(subtaskRows)
		};
	}

	function getDependencies(conf, subtaskId) {
		var key = 'subtask_dependence_' + subtaskId;
		var marker = conf[key];
		var deps = [];

		if (marker === undefined || marker === '' || marker === 'none') {
			return deps;
		}
		if (marker === 'many') {
			for (var i = 1; ; i++) {
				var dep = parseIntOrDefault(conf[key + '_' + i], 0);
				if (dep <= 0) {
					break;
				}
				deps.push(dep);
			}
			return deps;
		}

		var singleDep = parseIntOrDefault(marker, 0);
		if (singleDep > 0) {
			deps.push(singleDep);
		}
		return deps;
	}

	function buildSubtasks(conf) {
		var nSubtasks = parseIntOrDefault(conf.n_subtasks, 0);
		if (nSubtasks <= 0) {
			return [];
		}

		var nTests = parseIntOrDefault(conf.n_tests, 10);
		var inputPre = conf.input_pre || 'input';
		var inputSuf = conf.input_suf || 'txt';
		var outputPre = conf.output_pre || 'output';
		var outputSuf = conf.output_suf || 'txt';

		var subtasks = [];
		var prevEnd = 0;
		for (var i = 1; i <= nSubtasks; i++) {
			var endKey = 'subtask_end_' + i;
			var endDefault = i === nSubtasks ? nTests : prevEnd;
			var end = parseIntOrDefault(conf[endKey], endDefault);
			if (end < prevEnd) {
				end = prevEnd;
			}

			var start = prevEnd + 1;
			var tests = [];
			for (var t = start; t <= end; t++) {
				tests.push({
					id: t,
					input: inputPre + t + '.' + inputSuf,
					output: outputPre + t + '.' + outputSuf
				});
			}

			var typeKey = 'subtask_type_' + i;
			var usedTimeTypeKey = 'subtask_used_time_type_' + i;
			var scoreKey = 'subtask_score_' + i;

			subtasks.push({
				id: i,
				start: start,
				end: end,
				score: conf[scoreKey] === undefined ? '' : conf[scoreKey],
				type: conf[typeKey] || 'packed',
				typeDefault: conf[typeKey] === undefined,
				usedTimeType: conf[usedTimeTypeKey] || 'sum',
				usedTimeTypeDefault: conf[usedTimeTypeKey] === undefined,
				dependencies: getDependencies(conf, i),
				tests: tests
			});
			prevEnd = end;
		}

		return subtasks;
	}

	function renderDependencyLinks(dependencies) {
		if (!dependencies.length) {
			return '<span class="text-muted">none</span>';
		}
		var links = [];
		for (var i = 0; i < dependencies.length; i++) {
			var dep = dependencies[i];
			links.push(
				'<a href="#problem-conf-subtask-' + dep + '" data-subtask-link="' + dep + '">Subtask #' + dep + '</a>'
			);
		}
		return links.join(', ');
	}

	function renderTests(tests) {
		if (!tests.length) {
			return '<span class="text-muted">none</span>';
		}
		var html = '<ul class="problem-subtask-tests list-unstyled">';
		for (var i = 0; i < tests.length; i++) {
			var item = tests[i];
			html += '<li><code>' + escapeHTML(item.input) + '</code> &rarr; <code>' + escapeHTML(item.output) + '</code></li>';
		}
		html += '</ul>';
		return html;
	}

	function renderSubtasks(subtasks) {
		var html = '<div class="panel-group" id="problem-conf-subtask-accordion">';
		for (var i = 0; i < subtasks.length; i++) {
			var st = subtasks[i];
			var collapseId = 'problem-conf-subtask-collapse-' + st.id;

			html += '<div class="panel panel-default problem-subtask-card" id="problem-conf-subtask-' + st.id + '" data-subtask-id="' + st.id + '">';
			html += '<div class="panel-heading">';
			html += '<h4 class="panel-title">';
			html += '<a role="button" data-toggle="collapse" data-parent="#problem-conf-subtask-accordion" data-target="#' + collapseId + '" aria-expanded="false" aria-controls="' + collapseId + '">';
			html += 'Subtask #' + st.id;
			html += ' <span class="text-muted">(score: ' + (st.score === '' ? '?' : escapeHTML(st.score)) + ', tests: ' + st.start + '-' + st.end + ')</span>';
			html += '</a>';
			html += '</h4>';
			html += '</div>';

			html += '<div id="' + collapseId + '" class="panel-collapse collapse">';
			html += '<div class="panel-body">';

			html += '<p><strong>type:</strong> <code>' + escapeHTML(st.type) + '</code>';
			if (st.typeDefault) {
				html += ' <span class="text-muted problem-subtask-default-hint">(default)</span>';
			}
			html += '</p>';

			html += '<p><strong>used_time_type:</strong> <code>' + escapeHTML(st.usedTimeType) + '</code>';
			if (st.usedTimeTypeDefault) {
				html += ' <span class="text-muted problem-subtask-default-hint">(default)</span>';
			}
			html += '</p>';

			html += '<p><strong>dependencies:</strong> ' + renderDependencyLinks(st.dependencies) + '</p>';
			html += '<p><strong>tests:</strong></p>';
			html += renderTests(st.tests);
			html += '</div>';
			html += '</div>';
			html += '</div>';
		}
		html += '</div>';
		return html;
	}

	function initSection($section) {
		if ($section.data('problemSubtasksInited')) {
			return;
		}

		var $tableWrapper = $section.siblings('#problem-conf-table-wrapper').first();
		var $table = $tableWrapper.find('table').first();
		if (!$table.length) {
			return;
		}

		var parsed = readProblemConf($table);
		var subtasks = buildSubtasks(parsed.conf);
		if (!subtasks.length) {
			return;
		}

		var $container = $section.find('#problem-conf-subtasks-container');
		$container.html(renderSubtasks(subtasks));

		var $visualBtn = $section.find('[data-problem-subtasks-view="visual"]');
		var $rawBtn = $section.find('[data-problem-subtasks-view="raw"]');
		var $actionButtons = $section.find('[data-problem-subtasks-action]');

		var setView = function(mode) {
			var visualMode = mode !== 'raw';
			$visualBtn.toggleClass('btn-primary', visualMode).toggleClass('btn-default', !visualMode);
			$rawBtn.toggleClass('btn-primary', !visualMode).toggleClass('btn-default', visualMode);
			$actionButtons.prop('disabled', !visualMode);
			$container.toggle(visualMode);
			parsed.subtaskRows.toggle(!visualMode);
		};

		$section.off('.problemSubtasks');
		$section.on('click.problemSubtasks', '[data-problem-subtasks-view]', function() {
			setView($(this).attr('data-problem-subtasks-view'));
		});
		$section.on('click.problemSubtasks', '[data-problem-subtasks-action="expand-all"]', function() {
			$container.find('.panel-collapse').collapse('show');
		});
		$section.on('click.problemSubtasks', '[data-problem-subtasks-action="collapse-all"]', function() {
			$container.find('.panel-collapse').collapse('hide');
		});
		$section.on('click.problemSubtasks', 'a[data-subtask-link]', function(e) {
			e.preventDefault();
			var dep = parseIntOrDefault($(this).attr('data-subtask-link'), 0);
			if (dep <= 0) {
				return;
			}
			var $target = $container.find('#problem-conf-subtask-' + dep);
			if (!$target.length) {
				return;
			}
			$target.find('.panel-collapse').collapse('show');
			$('html, body').stop(true).animate({
				scrollTop: $target.offset().top - 80
			}, 200);
		});

		$section.show();
		setView('visual');
		$section.data('problemSubtasksInited', true);
	}

	function init(root) {
		var $root = root ? $(root) : $(document);
		var $sections = $root.find('#problem-conf-subtasks-section');
		if ($root.is('#problem-conf-subtasks-section')) {
			$sections = $sections.add($root);
		}
		$sections.each(function() {
			initSection($(this));
		});
	}

	window.UOJProblemSubtasks = {
		init: init
	};

	$(function() {
		init($(document));
	});
})();
