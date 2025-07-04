// locale
uojLocaleData = {
	"username": {
		"en": "Username",
		"zh-cn": "用户名"
	},
	"contests::total score": {
		"en": "Score",
		"zh-cn": "总分"
	},
	"contests::n participants": {
		"en": function(n) {
			return n + " participant" + (n <= 1 ? '' : 's');
		},
		"zh-cn": function(n) {
			return "共 " + n + " 名参赛者";
		}
	},
	"click-zan::good": {
		"en": "Good",
		"zh-cn": "好评"
	},
	"click-zan::bad": {
		"en": "Bad",
		"zh-cn": "差评"
	},
	"editor::use advanced editor": {
		"en": "use advanced editor",
		"zh-cn": "使用高级编辑器"
	},
	"editor::language": {
		"en": "Language",
		"zh-cn": "语言"
	},
	"editor::browse": {
		"en": "Browse",
		"zh-cn": "浏览"
	},
	"editor::upload by editor": {
		"en": "Upload by editor",
		"zh-cn": "使用编辑器上传"
	},
	"editor::upload from local": {
		"en": "Upload from local",
		"zh-cn": "从本地文件上传"
	},
	"quiz::empty answer alert": {
		"en": function(empty_list) {
			var ret = empty_list.length + " question";
			ret += empty_list.length <= 1 ? " is" : "s are"
			ret += " left unanswered (";
			for (var i = 0; i < empty_list.length && i < 3; i++) {
				if (i > 0) {
					ret += ", ";
				}
				ret += "#" + empty_list[i];
			}
			if (empty_list.length > 3) {
				ret += ", etc."
			}
			ret += "). Are your sure you want to submit?"
			return ret;
		},
		"zh-cn": function(empty_list) {
			var ret = "第";
			for (var i = 0; i < empty_list.length && i < 3; i++) {
				if (i > 0) {
					ret += "、";
				}
				ret += empty_list[i];
			}
			if (empty_list.length > 3) {
				ret += "等" + empty_list.length;
			}
			ret += "题尚未作答，你确定要提交吗？";
			return ret;
		}
	}
};

function uojLocale(name) {
	locale = $.cookie('uoj_locale');
	if (uojLocaleData[name] === undefined) {
		return '';
	}
	if (uojLocaleData[name][locale] === undefined) {
		locale = 'zh-cn';
	}
	val = uojLocaleData[name][locale];
	if (!$.isFunction(val)) {
		return val;
	} else {
		var args = [];
		for (var i = 1; i < arguments.length; i++) {
			args.push(arguments[i]);
		}
		return val.apply(this, args);
	}
}

// utility
function strToDate(str) {
	var a = str.split(/[^0-9]/);
	return new Date(
		parseInt(a[0]),
		parseInt(a[1]) - 1,
		parseInt(a[2]),
		parseInt(a[3]),
		parseInt(a[4]),
		parseInt(a[5]),
		0);
}
function dateToStr(date) {
	return date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate() + ' ' + date.getHours() + ':' + date.getMinutes() + ':' + date.getSeconds();
}
function toFilledStr(o, f, l) {
	var s = o.toString();
	while (s.length < l) {
		s = f.toString() + s;
	}
	return s;
}
function getPenaltyTimeStr(x) {
	if (x < 0) {
		return x + " sec";
	}
	var ss = toFilledStr(x % 60, '0', 2);
	x = Math.floor(x / 60);
	var mm = toFilledStr(x % 60, '0', 2);
	x = Math.floor(x / 60);
	var hh = x.toString();
	return hh + ':' + mm + ':' + ss;
}

function htmlspecialchars(str)
{
	var s = "";
	if (str.length == 0) return "";
	s = str.replace(/&/g, "&amp;");
	s = s.replace(/</g, "&lt;");
	s = s.replace(/>/g, "&gt;");
	s = s.replace(/"/g, "&quot;");
	return s;
}

function getColOfRating(rating) {
	if (rating < 1500) {
		var H = 300 - (1500 - 850) * 300 / 1650, S = 30 + (1500 - 850) * 70 / 1650, V = 50 + (1500 - 850) * 50 / 1650;
		if (rating < 300) rating = 300;
		var k = (rating - 300) / 1200;
		return ColorConverter.toStr(ColorConverter.toRGB(new HSV(H + (300 - H) * (1 - k), 30 + (S - 30) * k, 50 + (V - 50) * k)));
	}
	if (rating > 2500) {
		rating = 2500;
	}
	return ColorConverter.toStr(ColorConverter.toRGB(new HSV(300 - (rating - 850) * 300 / 1650, 30 + (rating - 850) * 70 / 1650, 50 + (rating - 850) * 50 / 1650)));
}
function getColOfScore(score) {
	if (score == 0) {
		return ColorConverter.toStr(ColorConverter.toRGB(new HSV(0, 100, 80)));
	} else if (score == 100) {
		return ColorConverter.toStr(ColorConverter.toRGB(new HSV(120, 100, 80)));
	} else {
		return ColorConverter.toStr(ColorConverter.toRGB(new HSV(30 + score * 60 / 100, 100, 90)));
	}
}

function getUserLink(username, rating, addSymbol) {
	if (!username) {
		return '';
	}
	if (addSymbol == undefined) {
		addSymbol = true;
	}
	var text = username;
	if (username.charAt(0) == '@') {
		username = username.substring(1);
	}
	if (addSymbol) {
		if (rating >= 2500) {
			text += '<sup>';
			for (var i = 2500; i <= rating; i += 200) {
				text += "&alefsym;"
			}
			text += "</sup>";
		}
	}
	return '<a class="uoj-username" href="' + uojHome + '/user/profile/' + username + '" style="color:' + getColOfRating(rating) + '">' + text + '</a>';
}
function getUserSpan(username, rating, addSymbol) {
	if (!username) {
		return '';
	}
	if (addSymbol == undefined) {
		addSymbol = true;
	}
	var text = username;
	if (username.charAt(0) == '@') {
		username = username.substring(1);
	}
	if (addSymbol) {
		if (rating >= 2500) {
			text += '<sup>';
			for (var i = 2500; i <= rating; i += 200) {
				text += "&alefsym;"
			}
			text += "</sup>";
		}
	}
	return '<span class="uoj-username" style="color:' + getColOfRating(rating) + '">' + text + '</span>';
}

function replaceWithHighlightUsername() {
	var username = $(this).text();
	var rating = $(this).data("rating");
	if (isNaN(rating)) {
		return;
	}
	if ($(this).data("link") != 0) {
		$(this).replaceWith(getUserLink(username, rating));
	} else {
		$(this).replaceWith(getUserSpan(username, rating));
	}
}

$.fn.uoj_honor = function() {
	return this.each(function() {
		var honor = $(this).text();
		var rating = $(this).data("rating");
		if (isNaN(rating)) {
			return;
		}
		if (rating >= 2500) {
			honor += '<sup>';
			for (var i = 2500; i <= rating; i += 200) {
				honor += "&alefsym;"
			}
			honor += "</sup>";
		}
		$(this).css("color", getColOfRating(rating)).html(honor);
	});
}

function showErrorHelp(name, err) {
	if (err) {
		$('#div-' + name).addClass('has-error');
		$('#help-' + name).text(err);
		return false;
	} else {
		$('#div-' + name).removeClass('has-error');
		$('#help-' + name).text('');
		return true;
	}
}
function getFormErrorAndShowHelp(name, val) {
	var err = val($('#input-' + name).val());
	return showErrorHelp(name, err);
}
function disableFormSubmit(form_name) {
	$("#button-submit-" + form_name).addClass('disabled');
	$("#form-" + form_name).submit(function () {
		return false;
	});
}

function validateSettingPassword(str) {
	if (str.length < 6) {
		return '密码长度不应小于6。';
	} else if (! /^[!-~]+$/.test(str)) {
		return '密码应只包含可见ASCII字符。';
	} else if (str != $('#input-confirm_password').val()) {
		return '两次输入的密码不一致。';
	} else {
		return '';
	}
}
function validatePassword(str) {
	if (str.length < 6) {
		return '密码长度不应小于6。';
	} else if (! /^[!-~]+$/.test(str)) {
		return '密码应只包含可见ASCII字符。';
	} else {
		return '';
	}
}
function validateEmail(str) {
	if (str.length > 50) {
		return '电子邮箱地址太长。';
	} else if (! /^(.+)@(.+)$/.test(str)) {
		return '电子邮箱地址非法。';
	} else {
		return '';
	}
}
function validateUsername(str) {
	if (str.length == 0) {
		return '用户名不能为空。';
	} else if (! /^[a-zA-Z0-9_]+$/.test(str)) {
		return '用户名应只包含大小写英文字母、数字和下划线。';
	} else {
		return '';
	}
}
function validateQQ(str) {
	if (str.length < 5) {
		return 'QQ的长度不应小于5。';
	} else if (str.length > 15) {
		return 'QQ的长度不应大于15。';
	} else if (/\D/.test(str)) {
		return 'QQ应只包含0~9的数字。';
	} else {
		return '';
	}
}
function validateMotto(str) {
	if (str.length > 50) {
		return '不能超过50字';
	} else {
		return '';
	}
}

// tags
$.fn.uoj_problem_tag = function() {
	return this.each(function() {
		$(this).attr('href', uojHome + '/problems?tag=' + encodeURIComponent($(this).text()));
	});
}
$.fn.uoj_blog_tag = function() {
	return this.each(function() {
		$(this).attr('href', '/archive?tag=' + encodeURIComponent($(this).text()));
	});
}

// click zan
function click_zan(zan_id, zan_type, zan_delta, node) {
	var loading_node = $('<div class="text-muted">loading...</div>');
	$(node).replaceWith(loading_node);
	$.post('/click-zan', {
		id : zan_id,
		delta : zan_delta,
		type : zan_type
	}, function(ret) {
		$(loading_node).replaceWith($(ret).click_zan_block());
	}).fail(function() {
		$(loading_node).replaceWith('<div class="text-danger">failed</div>');
	});
}

$.fn.click_zan_block = function() {
	return this.each(function() {
		var id = $(this).data('id');
		var type = $(this).data('type');
		var val = parseInt($(this).data('val'));
		var cnt = parseInt($(this).data('cnt'));
		if (isNaN(cnt)) {
			return;
		}
		if (val == 1) {
			$(this).addClass('uoj-click-zan-block-cur-up');
		} else if (val == 0) {
			$(this).addClass('uoj-click-zan-block-cur-zero');
		} else if (val == -1) {
			$(this).addClass('uoj-click-zan-block-cur-down');
		} else {
			return;
		}
		if (cnt > 0) {
			$(this).addClass('uoj-click-zan-block-positive');
		} else if (cnt == 0) {
			$(this).addClass('uoj-click-zan-block-neutral');
		} else {
			$(this).addClass('uoj-click-zan-block-negative');
		}
		
		var node = this;
		var up_node = $('<a href="#" class="uoj-click-zan-up"><span class="glyphicon glyphicon-thumbs-up"></span>'+uojLocale('click-zan::good')+'</a>').click(function(e) {
			e.preventDefault();
			click_zan(id, type, 1, node);
		});
		var down_node = $('<a href="#" class="uoj-click-zan-down"><span class="glyphicon glyphicon-thumbs-down"></span>'+uojLocale('click-zan::bad')+'</a>').click(function(e) {
			e.preventDefault();
			click_zan(id, type, -1, node);
		});
		
		$(this)
			.append(up_node)
			.append(down_node)
			.append($('<span class="uoj-click-zan-cnt">[<strong>' + (cnt > 0 ? '+' + cnt : cnt) + '</strong>]</span>'));
	});
}

// count down
function getCountdownStr(t) {
	var x = Math.floor(t);
	var ss = toFilledStr(x % 60, '0', 2);
	x = Math.floor(x / 60);
	var mm = toFilledStr(x % 60, '0', 2);
	x = Math.floor(x / 60);
	var hh = x.toString();
	
	var res = '<span style="font-size:30px">';
	res += '<span style="color:' + getColOfScore(Math.min(t / 10800 * 100, 100)) + '">' + hh + '</span>';
	res += ':';
	res += '<span style="color:' + getColOfScore(mm / 60 * 100) + '">' + mm + '</span>';
	res += ':';
	res += '<span style="color:' + getColOfScore(ss / 60 * 100) + '">' + ss + '</span>';
	res += '</span>'
	return res;
}

$.fn.countdown = function(rest, callback) {
	return this.each(function() {
		var start = new Date().getTime();
		var cur_rest = rest != undefined ? rest : parseInt($(this).data('rest'));
		var cur = this;
		var countdown = function() {
			var passed = Math.floor((new Date().getTime() - start) / 1000);
			if (passed >= cur_rest) {
				$(cur).html(getCountdownStr(0));
				if (callback != undefined) {
					callback();
				}
			} else {
				$(cur).html(getCountdownStr(cur_rest - passed));
				setTimeout(countdown, 1000);
			}
		}
		countdown();
	});
};

// update_judgement_status
update_judgement_status_list = []
update_judgement_status_base_delay = 500;
update_judgement_status_delay_adder = 500;
update_judgement_status_max_delay = 30 * 1000; // 30s
function update_judgement_status_details(id, base_delay = 0, delay_adder = 0) {
	update_judgement_status_list.push(id);
	if (base_delay > update_judgement_status_base_delay) {
		update_judgement_status_base_delay = base_delay;
	}
	if (delay_adder > update_judgement_status_delay_adder) {
		update_judgement_status_delay_adder = delay_adder;
	}
};

$(document).ready(function() {	
	var mean_delay = update_judgement_status_base_delay + update_judgement_status_delay_adder;

	function random_delay() {
		return -Math.log(1.0 - Math.random());
	}

	var next_delay = random_delay() * mean_delay;

	function update() {
		$.get("/submission-status-details", {
				get: update_judgement_status_list
			},
			function(data) {
				is_waiting = true;
				for (var i = 0; i < update_judgement_status_list.length; i++) {
					$("#status_details_" + update_judgement_status_list[i]).html(data[i].html);
					if (data[i].judged) {
						location.reload();
					}
					if (!data[i].waiting) {
						is_waiting = false;
					}
				}
				if (is_waiting) {
					mean_delay += update_judgement_status_delay_adder;
					if (mean_delay > update_judgement_status_max_delay) {
						mean_delay = update_judgement_status_max_delay;
					}
					next_delay = random_delay() * mean_delay;
				} else {
					next_delay = delay = update_judgement_status_base_delay;
				}
			}, 'json').always(
			function() {
    			setTimeout(update, next_delay);
	    	}
	    );
	}
	if (update_judgement_status_list.length > 0) {
		setTimeout(update, next_delay);
	}
});

// highlight
$.fn.uoj_highlight = function() {
	return $(this).each(function() {
		$(this).find("span.uoj-username").each(replaceWithHighlightUsername);
		$(this).find(".uoj-honor").uoj_honor();
		$(this).find(".uoj-score").each(function() {
			var score = $(this).data('score');
			if (isNaN(score)) {
    			score = parseFloat($(this).text());
    	    }
    	    if (isNaN(score)) {
				return;
			}
			var maxscore = parseFloat($(this).data('max'));
			if (isNaN(maxscore)) {
				$(this).css("color", getColOfScore(score));
			} else {
				$(this).css("color", getColOfScore(score / maxscore * 100));
			}
		});
		$(this).find(".uoj-status").each(function() {
			var success = parseInt($(this).data("success"));
			if(isNaN(success)){
				return;
			}
			if (success == 1) {
				$(this).css("color", ColorConverter.toStr(ColorConverter.toRGB(new HSV(120, 100, 80))));
			}
			else {
				$(this).css("color", ColorConverter.toStr(ColorConverter.toRGB(new HSV(0, 100, 100))));
			}
		});
		$(this).find(".uoj-problem-tag").uoj_problem_tag();
		$(this).find(".uoj-blog-tag").uoj_blog_tag();
		$(this).find(".uoj-click-zan-block").click_zan_block();
		$(this).find(".countdown").countdown();
		$(this).find(".uoj-readmore").readmore({
			moreLink: '<a href="#" class="text-right">more...</a>',
			lessLink: '<a href="#" class="text-right">close</a>',
		});
	});
};

$(document).ready(function() {
	$('body').uoj_highlight();
});

// contest notice
function checkNotice(lastTime) {
	$.post(uojHome + '/check-notice', {
			last_time : lastTime
		},
		function(data) {
            if (data === null) {
                return;
            }
			setTimeout(function() {
				checkNotice(data.time);
			}, 60000);
            for (var i = 0; i < data.msg.length; i++) {
                alert(data.msg[i]);
            }
		},
		'json'
	).fail(function() {
		setTimeout(function() {
			checkNotice(lastTime);
		}, 60000);
	});
}

// long table
$.fn.long_table = function(data, cur_page, header_row, get_row_str, config) {
	return this.each(function() {
		var table_div = this;
		
		$(table_div).html('');
		
		var page_len = config.page_len != undefined ? config.page_len : 10;
		
		if (!config.echo_full) {
			var n_rows = data.length;
			var n_pages = Math.max(Math.ceil(n_rows / page_len), 1);
			if (cur_page == undefined) {
				cur_page = 1;
			}
			if (cur_page < 1) {
				cur_page = 1;
			} else if (cur_page > n_pages) {
				cur_page = n_pages;
			}
			var cur_start = (cur_page - 1) * page_len;
		} else {
			var n_rows = data.length;
			var n_pages = 1;
			cur_page = 1;
			var cur_start = (cur_page - 1) * page_len;
		}
		
		var div_classes = config.div_classes != undefined ? config.div_classes : ['table-responsive'];
		var table_classes = config.table_classes != undefined ? config.table_classes : ['table', 'table-bordered', 'table-hover', 'table-striped', 'table-text-center'];
		
		var now_cnt = 0;
		var tbody = $('<tbody />')
		for (var i = 0; i < page_len && cur_start + i < n_rows; i++) {
			now_cnt++;
			if (config.get_row_index) {
				tbody.append(get_row_str(data[cur_start + i], cur_start + i));
			} else {
				tbody.append(get_row_str(data[cur_start + i]));
			}
		}
		if (now_cnt == 0) {
			tbody.append('<tr><td colspan="233">无</td></tr>');
		}
		
		$(table_div).append(
			$('<div class="' + div_classes.join(' ') + '" />').append(
				$('<table class="' + table_classes.join(' ') + '" />').append(
					$('<thead />').append(header_row)
				).append(
					tbody
				)
			)
		);
		
		if (config.print_after_table != undefined) {
			$(table_div).append(config.print_after_table());
		}
		
		if (config.mathjax) {
			MathJax.typesetPromise([table_div]);
		}
		
		var get_page_li = function(p, h) {
			if (p == -1) {
				return $('<li></li>').addClass('disabled').append($('<a></a>').append(h));
			}
			
			var li = $('<li></li>');
			if (p == cur_page) {
				li.addClass('active');
			}
			li.append(
				$('<a></a>').attr('href', '#' + table_div.id).append(h).click(function(e) {
					if (config.prevent_focus_on_click) {
						e.preventDefault();
					}
					$(table_div).long_table(data, p, header_row, get_row_str, config);
				})
			);
			return li;
		};
		var get_pagination = function() {
			var pagination = $('<ul class="pagination top-buffer-no bot-buffer-sm"></ul>');
			if (cur_page > 1) {
				pagination.append(get_page_li(cur_page - 1, '<span class="glyphicon glyphicon glyphicon-backward"></span>'));
			} else {
				pagination.append(get_page_li(-1, '<span class="glyphicon glyphicon glyphicon-backward"></span>'));
			}
			var max_extend = config.max_extend != undefined ? config.max_extend : 5;
			for (var i = Math.max(cur_page - max_extend, 1); i <= Math.min(cur_page + max_extend, n_pages); i++) {
				pagination.append(get_page_li(i, i.toString()));
			}
			if (cur_page < n_pages) {
				pagination.append(get_page_li(cur_page + 1, '<span class="glyphicon glyphicon glyphicon-forward"></span>'));
			} else {
				pagination.append(get_page_li(-1, '<span class="glyphicon glyphicon glyphicon-forward"></span>'));
			}
			return pagination;
		}
		
		if (n_pages > 1) {
			$(table_div).append($('<div class="text-center"></div>').append(get_pagination()));
			if (config.top_pagination) {
				$(table_div).prepend($('<div class="text-center"></div>').append(get_pagination()));
			}
		}
	});
};

// code mirror
function require_codemirror(config, callback) {
	if ($('link[href="' + uojHome + '/js/codemirror/lib/codemirror.css' + '"]').length == 0) {
		$('<link type="text/css" rel="stylesheet" href="' + uojHome + '/js/codemirror/lib/codemirror.css' + '" />').appendTo('head');
	}
	$LAB.script(uojHome + '/js/codemirror/lib/codemirror.js')
		.wait()
		.script(uojHome + '/js/codemirror/addon/mode/overlay.js')
		.script(uojHome + '/js/codemirror/addon/selection/active-line.js')
		.wait(callback)
}

function get_codemirror_mode(lang) {
	switch (lang) {
		case 'C++':
		case 'C++11':
		case 'C++14':
		case 'C++17':
		case 'C++20':
			return 'text/x-c++src';
		case 'C':
			return 'text/x-csrc';
		case 'Python2.7':
		case 'Python3':
			return 'text/x-python';
		case 'Java7':
		case 'Java8':
		case 'Java11':
		case 'Java14':
		case 'Java17':
			return 'text/x-java';
		case 'Pascal':
			return 'text/x-pascal';
		case 'text':
			return 'text/plain';
		default:
			return 'text/plain';
	}
};
function require_codemirror_mode(mode, callback) {
	var name = 'none';
	switch (mode) {
		case 'text/x-c++src':
		case 'text/x-csrc':
		case 'text/x-java':
			name = 'clike';
			break;
		case 'text/x-python':
			name = 'python';
			break;
		case 'text/x-pascal':
			name = 'pascal';
			break;
	}
	if (name !== 'none') {
		$LAB.script(uojHome + '/js/codemirror/mode/' + name + '/' + name + '.js')
			.wait(callback);
	} else {
		setTimeout(callback, 0);
	}
};

function is_localStorage_supported() {
	if (typeof(Storage) === "undefined") {
		console.log('autosave_locally: Sorry! No Web Storage support..');
		return false;
	} else {
		return true;
	}
}

function local_var_at_this_page(name, val) {
	if (!is_localStorage_supported()) {
		return;
	}

	var url = window.location.href;
	var hp = url.indexOf('#');
	var uri = hp == -1 ? url : url.substring(0, hp);
	var full_name = name + '@' + uri;

	if (val === undefined) {
		return localStorage.getItem(full_name);
	} else {
		localStorage.setItem(full_name, val)
	}
}

// auto save
function autosave_locally(interval, name, target) {
	if (!is_localStorage_supported()) {
		return;
	}

	target.val(local_var_at_this_page(name));
	var save = function() {
		local_var_at_this_page(name, target.val());
		setTimeout(save, interval);
	};
	setTimeout(save, interval);
}

// source code form group
$.fn.source_code_form_group = function(name, text, langs_options_html) {
	return this.each(function() {
		var input_language_id = 'input-' + name + '_language';
		var input_language_name = name + '_language';
		var input_upload_type_name = name + '_upload_type';
		var input_editor_id = 'input-' + name + '_editor';
		var input_editor_name = name + '_editor';
		var input_file_id = 'input-' + name + '_file';
		var input_file_name = name + '_file';

		var div_help_language_id = 'div-help-' + name + '_language';
		var div_editor_id = 'div-' + name + '_editor';
		var div_file_id = 'div-' + name + '_file';

		var help_file_id = 'help-' + name + '_file';

		var input_language =
			$('<select id="' + input_language_id + '" name="' + input_language_name + '" class="form-control input-sm"/>')
				.html(langs_options_html);
		var input_upload_type_editor = $('<input type="radio" name="' + input_upload_type_name + '" value="editor" />');
		var input_upload_type_file = $('<input type="radio" name="' + input_upload_type_name + '" value="file" />');
		var input_file = $('<input type="file" id="' + input_file_id + '" name="' + input_file_name + '" style="display: none" />');
		var input_file_path = $('<input class="form-control" type="text" readonly="readonly" />');
		var input_editor = $('<textarea class="form-control" id="' + input_editor_id + '" name="' + input_editor_name + '"></textarea>');
		var input_use_advanced_editor = $('<input type="checkbox">');

		var div_editor =
			$('<div id="' + div_editor_id + '" class="col-sm-12"/>')
				.append(input_editor)
				.append($('<div class="checkbox text-right" />')
					.append($('<label />')
						.append(input_use_advanced_editor)
						.append(' ' + uojLocale('editor::use advanced editor'))
					)
				)
		var div_file =
			$('<div id="' + div_file_id + '" class="col-sm-12"/>')
				.append(input_file)
				.append($('<div class="input-group"/>')
					.append(input_file_path)
					.append($('<span class="input-group-btn"/>')
						.append($('<button type="button" class="btn btn-primary">'+'<span class="glyphicon glyphicon-folder-open"></span> '+uojLocale('editor::browse')+'</button>')
							.css('width', '100px')
							.click(function() {
								input_file.click();
							})
						)
					)
				)
				.append($('<span class="help-block" id="' + help_file_id + '"></span>'))
		var div_help_language = $('<div id="' + div_help_language_id + '" class="col-sm-12 text-warning top-buffer-sm">');

		var show_help_lang = function() {
			if ($(this).val().startsWith('Java')) {
				div_help_language.text('注意：Java 程序源代码中不应指定所在的 package。我们会在源代码中找到第一个被定义的类并以它的 main 函数为程序入口点。');
			} else {
				div_help_language.text('');
			}
		};

		var advanced_editor = null;
		var advanced_editor_init = function() {
			require_codemirror({}, function() {
				var mode = get_codemirror_mode(input_language.val());
				require_codemirror_mode(mode, function() {
					if (advanced_editor != null) {
						return;
					}
					advanced_editor = CodeMirror.fromTextArea(input_editor[0], {
						mode: mode,
						lineNumbers: true,
						matchBrackets: true,
						lineWrapping: true,
						styleActiveLine: true,
						indentUnit: 4,
						indentWithTabs: true,
						theme: 'default'
					});
					advanced_editor.on('change', function() {
						advanced_editor.save();
					});
					$(advanced_editor.getWrapperElement()).css('box-shadow', '0 2px 10px rgba(0,0,0,0.2)');
					advanced_editor.focus();
				});
			});
		}

		var save_prefer_upload_type = function(type) {
			$.cookie('uoj_source_code_form_group_preferred_upload_type', type, { expires: 7, path: '/' });
		};

		autosave_locally(2000, name, input_editor);

		var prefer_upload_type = $.cookie('uoj_source_code_form_group_preferred_upload_type');
		if (prefer_upload_type === null) {
			prefer_upload_type = 'editor';
		}
		if (prefer_upload_type == 'file') {
			input_upload_type_file[0].checked = true;
			div_editor.css('display', 'none');
		} else {
			input_upload_type_editor[0].checked = true;
			div_file.css('display', 'none');

			if (prefer_upload_type == 'advanced') {
				input_use_advanced_editor[0].checked = true;
			}
		}

		input_language.each(show_help_lang);
		input_language.change(show_help_lang);
		input_language.change(function() {
			if (advanced_editor != null) {
				var mode = get_codemirror_mode(input_language.val());
				require_codemirror_mode(mode, function() {
					if (mode != get_codemirror_mode(input_language.val())) {
						return;
					}
					advanced_editor.setOption('mode', mode);
				});
			}
		})
		input_upload_type_editor.click(function() {
			div_editor.show('fast');
			div_file.hide('fast');
			save_prefer_upload_type('editor');
		});
		input_upload_type_file.click(function() {
			div_file.show('fast');
			div_editor.hide('fast');
			save_prefer_upload_type('file');
		});
		input_file.change(function() {
			input_file_path.val(input_file.val());
		});
		input_use_advanced_editor.click(function() {
			if (this.checked) {
				advanced_editor_init();
				save_prefer_upload_type('advanced');
			} else {
				if (advanced_editor != null) {
					advanced_editor.toTextArea();
					advanced_editor = null;
					input_editor.focus();
				}
				save_prefer_upload_type('editor');
			}
		});

		$(this)
			.append($('<label class="col-sm-2 control-label"><div class="text-left">' + text + '</div></label>'))
			.append($('<label class="col-sm-1 control-label" for="' + input_language_name + '">'+uojLocale('editor::language')+'</label>'))
			.append($('<div class="col-sm-2"/>')
				.append(input_language)
			)
			.append($('<div class="col-sm-offset-3 col-sm-2 radio"/>')
				.append($('<label/>')
					.append(input_upload_type_editor)
					.append(' '+uojLocale('editor::upload by editor'))
				)
			)
			.append($('<div class="col-sm-2 radio"/>')
				.append($('<label/>')
					.append(input_upload_type_file)
					.append(' '+uojLocale('editor::upload from local'))
				)
			)
			.append(div_help_language)
			.append(div_editor)
			.append(div_file);

		if (prefer_upload_type == 'advanced') {
			var check_advanced_init = function() {
				if (div_editor.is(':visible')) {
					advanced_editor_init();
				} else {
					setTimeout(check_advanced_init, 1);
				}
			}
			check_advanced_init();
		}
	});
}

// text file form group
$.fn.text_file_form_group = function(name, text) {
	return this.each(function() {
		var input_upload_type_name = name + '_upload_type';
		var input_editor_id = 'input-' + name + '_editor';
		var input_editor_name = name + '_editor';
		var input_file_id = 'input-' + name + '_file';
		var input_file_name = name + '_file';

		var div_editor_id = 'div-' + name + '_editor';
		var div_file_id = 'div-' + name + '_file';

		var help_file_id = 'help-' + name + '_file';

		var input_upload_type_editor = $('<input type="radio" name="' + input_upload_type_name + '" value="editor" />');
		var input_upload_type_file = $('<input type="radio" name="' + input_upload_type_name + '" value="file" />');
		var input_file = $('<input type="file" id="' + input_file_id + '" name="' + input_file_name + '" style="display: none" />');
		var input_file_path = $('<input class="form-control" type="text" readonly="readonly" />');
		var input_editor = $('<textarea class="form-control" id="' + input_editor_id + '" name="' + input_editor_name + '"></textarea>');
		var input_use_advanced_editor = $('<input type="checkbox">');

		var div_editor =
			$('<div id="' + div_editor_id + '" class="col-sm-12"/>')
				.append(input_editor)
				.append($('<div class="checkbox text-right" />')
					.append($('<label />')
						.append(input_use_advanced_editor)
						.append(' ' + uojLocale('editor::use advanced editor'))
					)
				)
		var div_file =
			$('<div id="' + div_file_id + '" class="col-sm-12"/>')
				.append(input_file)
				.append($('<div class="input-group"/>')
					.append(input_file_path)
					.append($('<span class="input-group-btn"/>')
						.append($('<button type="button" class="btn btn-primary">'+'<span class="glyphicon glyphicon-folder-open"></span> '+uojLocale('editor::browse')+'</button>')
							.css('width', '100px')
							.click(function() {
								input_file.click();
							})
						)
					)
				)
				.append($('<span class="help-block" id="' + help_file_id + '"></span>'))

		var advanced_editor = null;
		var advanced_editor_init = function() {
			require_codemirror({}, function() {
				var mode = get_codemirror_mode('text');
				require_codemirror_mode(mode, function() {
					if (advanced_editor != null) {
						return;
					}
					advanced_editor = CodeMirror.fromTextArea(input_editor[0], {
						mode: mode,
						lineNumbers: true,
						matchBrackets: true,
						lineWrapping: true,
						styleActiveLine: true,
						indentUnit: 4,
						indentWithTabs: true,
						theme: 'default'
					});
					advanced_editor.on('change', function() {
						advanced_editor.save();
					});
					$(advanced_editor.getWrapperElement()).css('box-shadow', '0 2px 10px rgba(0,0,0,0.2)');
					advanced_editor.focus();
				});
			});
		}

		var save_prefer_upload_type = function(type) {
			$.cookie('uoj_text_file_form_group_preferred_upload_type', type, { expires: 7, path: '/' });
		};

		autosave_locally(2000, name, input_editor);

		var prefer_upload_type = $.cookie('uoj_text_file_form_group_preferred_upload_type');
		if (prefer_upload_type === null) {
			prefer_upload_type = 'editor';
		}
		if (prefer_upload_type == 'file') {
			input_upload_type_file[0].checked = true;
			div_editor.css('display', 'none');
		} else {
			input_upload_type_editor[0].checked = true;
			div_file.css('display', 'none');

			if (prefer_upload_type == 'advanced') {
				input_use_advanced_editor[0].checked = true;
			}
		}

		input_upload_type_editor.click(function() {
			div_editor.show('fast');
			div_file.hide('fast');
			save_prefer_upload_type('editor');
		});
		input_upload_type_file.click(function() {
			div_file.show('fast');
			div_editor.hide('fast');
			save_prefer_upload_type('file');
		});
		input_file.change(function() {
			input_file_path.val(input_file.val());
		});
		input_use_advanced_editor.click(function() {
			if (this.checked) {
				advanced_editor_init();
				save_prefer_upload_type('advanced');
			} else {
				if (advanced_editor != null) {
					advanced_editor.toTextArea();
					advanced_editor = null;
					input_editor.focus();
				}
				save_prefer_upload_type('editor');
			}
		});

		$(this)
			.append($('<label class="col-sm-2 control-label"><div class="text-left">' + text + '</div></label>'))
			.append($('<div class="top-buffer-sm" />'))
			.append($('<div class="col-sm-offset-6 col-sm-2 radio"/>')
				.append($('<label/>')
					.append(input_upload_type_editor)
					.append(' '+uojLocale('editor::upload by editor'))
				)
			)
			.append($('<div class="col-sm-2 radio"/>')
				.append($('<label/>')
					.append(input_upload_type_file)
					.append(' '+uojLocale('editor::upload from local'))
				)
			)
			.append(div_editor)
			.append(div_file);

		if (prefer_upload_type == 'advanced') {
			var check_advanced_init = function() {
				if (div_editor.is(':visible')) {
					advanced_editor_init();
				} else {
					setTimeout(check_advanced_init, 1);
				}
			}
			check_advanced_init();
		}
	});
}

function quiz_problem_form_init(form_name) {
	var inputs = {}

	$('#form-' + form_name + ' *').filter(':input').each(function() {
		var name = this.name;
		switch (this.type) {
			case 'checkbox':
				inputs[name] = this.type;
				if (is_localStorage_supported()) {
					var varname = name + '_' + this.value;
					var checked = local_var_at_this_page(varname);
					if (checked !== null) {
						this.checked = checked === 'true';
					}
					$(this).change(function() {
						local_var_at_this_page(varname, this.checked);
					});
				}
				break;
			case 'radio':
				inputs[name] = this.type;
				if (is_localStorage_supported()) {
					var value = local_var_at_this_page(name);
					if (value !== null) {
						this.checked = this.value == value;
					}
					$(this).change(function() {
						local_var_at_this_page(name, $('input[name="' + name + '"]:checked').val());
					});
				}
				break;
		}
	});

	$('#form-' + form_name).submit(function(e) {
		var ok = true;
		var empty_list = [];
		for (var name in inputs) {
			var type = inputs[name];
			var qid = parseInt(name.substring((form_name + '_Q').length));
			switch (type) {
				case 'checkbox':
				case 'radio':
					if ($('input[name="' + name + '"]:checked').length == 0) {
						empty_list.push(qid);
					}
					break;
			}
		}

		if (empty_list.length > 0) {
			if (!confirm(uojLocale("quiz::empty answer alert", empty_list))) {
				ok = false;
			}
		}

		if (ok) {
			disableFormSubmit(form_name);
		}

		return ok;
	});
}

// custom test
function custom_test_onsubmit(response_text, div_result, url) {
	if (response_text != '') {
		$(div_result).html('<div class="text-danger">' + response_text + '</div>');
		return;
	}
	var update = function() {
		var can_next = true;
		$.get(url,
			function(data) {
				if (data.judged === undefined) {
					$(div_result).html('<div class="text-danger">error</div>');
				} else {
					var judge_status = $('<table class="table table-bordered table-text-center"><tr class="info">' + data.html + '</tr></table>');
					$(div_result).empty();
					$(div_result).append(judge_status);
					if (data.judged) {
						var judge_result = $(data.result);
						judge_result.css('display', 'none');
						$(div_result).append(judge_result);
						judge_status.hide(500);
						judge_result.slideDown(500);
						can_next = false;
					}
				}
			}, 'json')
		.always(function() {
			if (can_next) {
				setTimeout(update, 500);
			}
		});
	};
	setTimeout(update, 500);
}

// comment
function showCommentReplies(id, replies) {
	var toggleFormReply = function(from, text) {
		if (text == undefined) {
			text = '';
		}
		
		var p = '#comment-body-' + id;
		var q = '#div-form-reply';
		var r = '#input-reply_comment';
		var t = '#input-reply_id';
		if ($(q).data('from') != from) {
			$(q).data('from', from);
			$(q).hide('fast', function() {
				$(this).appendTo(p).show('fast', function() {
					$(t).val(id);
					$(r).val(text).focus();
				});
			});

		} else if ($(q).css('display') != 'none') {
			$(q).appendTo(p).hide('fast');
		} else {
			$(q).appendTo(p).show('fast', function() {
				$(t).val(id);
				$(r).val(text).focus();
			});
		}
	}

	$('#reply-to-' + id).click(function(e) {
		e.preventDefault();
		toggleFormReply(id);
	});
	
	if (replies.length == 0) {
		return;
	}
	
	$("#replies-" + id).long_table(
		replies,
		1,
		'<tr>' +
			'<th>评论回复</th>' +
		'</tr>',
		function(reply) {
			return $('<tr id="' + 'comment-' + reply.id + '" />').append(
				$('<td />').append(
					$('<div class="comtbox6">' + getUserLink(reply.poster, reply.poster_rating) + '：' + reply.content + '</div>')
				).append(
					$('<ul class="text-right list-inline bot-buffer-no" />').append(
						'<li>' + '<small class="text-muted">' + reply.post_time + '</small>' + '</li>'
					).append(
						$('<li />').append(
							$('<a href="#">回复</a>').click(function (e) {
								e.preventDefault();
								toggleFormReply(reply.id, '回复 @' + reply.poster + '：');
							})
						)
					)
				)
			).uoj_highlight();
		}, {
			table_classes: ['table', 'table-condensed'],
			page_len: 5,
			mathjax: true,
			prevent_focus_on_click: true
		}
	);
}

function getACMStandingsMeta() {
	var stat = {};
	var full_score = 0;
	
	for (var k = 0; k < problems.length; k++) {
		var pid = problems[k];
		stat[pid] = {};
		stat[pid].cnt = 0;
		stat[pid].ac_cnt = 0;
		stat[pid].earliest = null;
		if (("problem_" + pid) in bonus) {
			for (var j in score) {
				if (score[j][k] != undefined) {
					stat[pid].cnt += score[j][k][3];
					if (score[j][k][1] === -1200) {
						stat[pid].ac_cnt++;
					}
				}
			}
		} else {
			full_score += 100;
			for (var j in score) {
				if (score[j][k] != undefined) {
					stat[pid].cnt += score[j][k][3];
					if (score[j][k][0] === 100) {
						stat[pid].ac_cnt++;
						if (stat[pid].earliest === null || score[j][k][2] < stat[pid].earliest) {
							stat[pid].earliest = score[j][k][2];
						}
					}
				}
			}
		}
	}
	return { stat, full_score };
}

function setACMStandingsTH(th, i, meta) {
	if (i == -3) {
		return $(th).css('width', '34px').text('#');
	} else if (i == -2) {
		if (problems.length <= 10) {
			$(th).css('width', '114px');
		}
		return $(th).text(uojLocale('username'));
	} else if (i == -1) {
		return $(th).css('width', '57px').text('=');
	}

	var pid = problems[i];
	
	$(th).css('width', '57px');
	if (("problem_" + pid) in bonus) {
		$(th).attr('title', '附加题，通过后减免20分钟罚时');
	}
	var th_str = '<div><a href="/contest/' + contest_id + '/problem/' + pid + '">' + String.fromCharCode('A'.charCodeAt(0) + i);
	if (("problem_" + pid) in bonus) {
		th_str += '*';
	}
	th_str += '</a></div>';
	if (meta && pid in meta.stat) {
		th_str += '<div>' + meta.stat[pid].ac_cnt + '/' + meta.stat[pid].cnt + '</div>';
	}
	return $(th).html(th_str);
}

function setACMStandingsTD(td, row, i, meta) {
	if (i == -3) {
		return $(td).attr('class', '').html(row[3]);
	} else if (i == -2) {
		if (2 in row[2]) {
			let td_title = row[2][2]['team_name'] + "\n";
			for (var i = 0; i < row[2][2]['members'].length; i++) {
				td_title += row[2][2]['members'][i]['name'];
				td_title += "  （";
				td_title += row[2][2]['members'][i]['organization'];
				td_title += "）";
				if (i < row[2][2]['members'].length - 1) {
					td_title += "\n";
				}
			}
			return $(td).attr('class', '').attr('title', td_title).html(
				'<div class="text-center" style="overflow-wrap:anywhere;">' +
					'<small><strong>' + htmlspecialchars(row[2][2]['team_name']) + '</strong></small>' +
				'</div>' +
				'<div>' +
					'<small>' + getUserLink(row[2][0], row[2][1]) + '</small>' +
				'</div>'
			);
		} else {
			return $(td).attr('class', '').html(getUserLink(row[2][0], row[2][1]));
		}
	} else if (i == -1) {
		let td_title = "总分：" + row[0] + "\n";
		td_title += "罚时：" + row[1] + "，即 " + getPenaltyTimeStr(row[1]);
		return $(td).attr('class', 'standings-score-td').attr('title', td_title).html(
			'<div>' +
				'<span class="uoj-score" data-max="' + meta.full_score + '" style="color:' + getColOfScore(row[0] * 100 / meta.full_score) + '">' +
					row[0] +
				'</span>' +
			'</div>' +
			'<div>' +
				'<small>' + getPenaltyTimeStr(row[1]) + '</small>' +
			'</div>'
		);
	}

	var col = score[row[2][0]][i];
	
	$(td).attr('class', 'standings-score-td');
	
	if (col === undefined) {
		return $(td).html('');
	}
	
	var td_title = String.fromCharCode('A'.charCodeAt(0) + i) + "题\n";
	var td_content = '';
	
	if (("problem_" + problems[i]) in bonus) {
		td_content += '<div>';
		if (col[0] !== null) {
			if (col[1] == -1200) {
				td_content += '<a href="/submission/' + col[2] + '" class="uoj-score" data-score="100" style="color:' + getColOfScore(100) + '">';
				td_content += '+';
				td_content += '</a>';
				
				td_title += col[3] + " 次有效提交后通过，减免罚时 20 分钟";
			} else {
				td_content += '<a href="/submission/' + col[2] + '" class="uoj-score" data-score="0" style="color:' + getColOfScore(0) + '">';
				td_content += 0;
				td_content += '</a>';
				
				td_title += "尚未通过，未减免罚时";
				td_title += "\n" + col[3] + " 次有效提交";
			}
			if (col[5] > 0) {
				td_content += ' + ';
				td_title += "\n" + "因封榜有 " + col[5] + " 次提交结果未知";
			}
		} else {
			td_title += "封榜后提交了 " + col[5] + " 次，结果未知";
		}
		
		if (col[5] > 0) {
			td_content += '<strong class="text-muted">?</strong>';
		}
		td_content += '</div>';
		
		if (col[4] > 0) {
			td_content += '<div><small>';
			td_content += '(+' + col[4] + ')';
			td_content += '</small></div>';
		}
	} else {
		td_content += '<div>';
		if (col[0] !== null) {
			td_content += '<a href="/submission/' + col[2] + '" class="uoj-score" style="color:' + getColOfScore(col[0]) + '">';
			td_content += col[0];
			td_content += '</a>';
			
			td_title += "得分：" + col[0] + " 分";
			td_title += "\n" + col[3] + " 次有效提交";
			
			if (col[5] > 0) {
				td_content += ' + ';
				td_title += "\n" + "因封榜有 " + col[5] + " 次提交结果未知";
			}
		} else {
			td_title += "封榜后提交了 " + col[5] + " 次，结果未知";
		}
		if (col[5] > 0) {
			td_content += '<strong class="text-muted">?</strong>';
		}
		td_content += '</div>';
		
		if (col[0] > 0) {
			let orig_penalty = col[1] - col[4] * 60 * 20;
			td_content += '<div><small>' + getPenaltyTimeStr(orig_penalty) + '</small></div>';
			
			if (col[4] > 0) {
				td_title += "\n" + col[4] + " 次提交计入罚时";
			}
			td_title += "\n" + "罚时：" + orig_penalty;
			if (col[4] > 0) {
				td_title += " + " + col[4] + " × 1200 = " + col[1];
			}
			td_title += "，即 " + getPenaltyTimeStr(orig_penalty);
		}
		
		if (col[4] > 0) {
			td_content += '<div><small>';
			td_content += '(+' + col[4] + ')';
			td_content += '</small></div>';
		}
	}

	if (meta.stat[problems[i]].earliest === col[2]) {
		$(td).addClass('first-blood');
	}
	return $(td).attr('title', td_title).html(td_content);
}

// standings
function showStandings() {
	if (contest_rule == 'UOJ-OI' || contest_rule == 'UOJ-IOI') {
		$("#standings").long_table(
			standings,
			1,
			'<tr>' +
				'<th style="width:5em">#</th>' +
				'<th style="width:14em">'+uojLocale('username')+'</th>' +
				'<th style="width:5em">'+uojLocale('contests::total score')+'</th>' +
				$.map(problems, function(col, idx) {
					return '<th style="width:8em;">' + '<a href="/contest/' + contest_id + '/problem/' + col + '">' + String.fromCharCode('A'.charCodeAt(0) + idx) + '</a>' + '</th>';
				}).join('') +
			'</tr>',
			function(row) {
				var col_tr = '';
				if (myname != row[2][0]) {
					col_tr += '<tr>';
				} else {
					col_tr += '<tr class="warning">';
				}
				col_tr += '<td>' + row[3] + '</td>';
				col_tr += '<td>' + getUserLink(row[2][0], row[2][1]) + '</td>';
				col_tr += '<td>' + '<div><span class="uoj-score" data-max="' + problems.length * 100 + '" style="color:' + getColOfScore(row[0] / problems.length) + '">' + row[0] + '</span></div>' + '<div>' + getPenaltyTimeStr(row[1]) + '</div></td>';
				for (var i = 0; i < problems.length; i++) {
					col_tr += '<td>';
					col = score[row[2][0]][i];
					if (col != undefined) {
						col_tr += '<div><a href="/submission/' + col[2] + '" class="uoj-score" style="color:' + getColOfScore(col[0]) + '">' + col[0] + '</a></div>';
						if (standings_version < 2) {
							col_tr += '<div>' + getPenaltyTimeStr(col[1]) + '</div>';
						} else {
							if (col[0] > 0) {
								col_tr += '<div>' + getPenaltyTimeStr(col[1]) + '</div>';
							}
						}
					}
					col_tr += '</td>';
				}
				col_tr += '</tr>';
				return col_tr;
			}, {
				table_classes: ['table', 'table-bordered', 'table-striped', 'table-text-center', 'table-vertical-middle', 'table-condensed'],
				page_len: 100,
				top_pagination: true,
			    max_extend: 10,
				print_after_table: function() {
					return '<div class="text-right text-muted">' + uojLocale("contests::n participants", standings.length) + '</div>';
				}
			}
		);
	} else if (contest_rule == 'UOJ-ACM') {
		var meta = getACMStandingsMeta();
		var header = $('<tr />');
		for (let i = -3; i < problems.length; i++) {
			header.append(setACMStandingsTH(document.createElement('th'), i, meta));
		}
	    
	    $("#standings").long_table(
		    standings,
		    1,
			header,
		    function(row) {
				var tr = $('<tr />').css('height', '57px');
				if (myname == row[2][0]) {
					tr.addClass('warning');
				}
			    for (let i = -3; i < problems.length; i++) {
					tr.append(setACMStandingsTD(document.createElement('td'), row, i, meta));
			    }
				return tr;
		    }, {
			    table_classes: ['table', 'table-bordered', 'table-striped', 'table-text-center', 'table-vertical-middle', 'table-condensed'],
			    page_len: 100,
			    top_pagination: true,
			    max_extend: 10,
			    print_after_table: function() {
				    return '<div class="text-right text-muted">' + uojLocale("contests::n participants", standings.length) + '</div>';
			    }
		    }
	    );
	}
}
