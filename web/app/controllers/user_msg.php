<?php

Auth::check() || redirectToLogin();

$enter = UOJRequest::user(UOJRequest::GET, 'enter');
if ($enter !== false) {
	($enter && $enter['username'] !== Auth::id()) || UOJResponse::page404();
}

function handleMsgPost() {
	if (!isset($_POST['message'])) {
		return 'fail';
	}
	if (0 > strlen($_POST['message']) || strlen($_POST['message']) > 65535) {
		return 'fail';
	}
	$receiver = UOJRequest::user(UOJRequest::POST, 'receiver');
	if (!$receiver) {
		return 'fail';
	}
	$message = $_POST['message'];

	if ($receiver['username'] === Auth::id()) {
		return 'fail';
	}

	DB::insert([
		"insert into user_msg",
		"(sender, receiver, message, send_time)",
		"values", DB::tuple([Auth::id(), $receiver['username'], $message, DB::now()])
	]);
	return "ok";
}

function getConversations() {
	$username = Auth::id();
	$res = DB::selectAll([
		"select * from user_msg",
		"where", DB::lor([
			"sender" => $username,
			"receiver" => $username
		]),
		"order by send_time DESC"
	]);
	$ret = [];
	foreach ($res as $msg) {
		if ($msg['sender'] !== $username) {
			if (isset($ret[$msg['sender']])) {
				$ret[$msg['sender']][1] |= ($msg['read_time'] == null);
				continue;
			}
			$ret[$msg['sender']] = [$msg['send_time'], ($msg['read_time'] == null)];
		} else {
			if (isset($ret[$msg['receiver']])) {
				continue;
			}
			$ret[$msg['receiver']] = [$msg['send_time'], 0];
		}
	}
	$res = [];
	foreach ($ret as $name => $con) {
		$res[] = [$con[0], $con[1], $name];
	}
	usort($res, function($a, $b) {
		return -strcmp($a[0], $b[0]);
	});
	return json_encode($res);
}

function getHistory() {
	$username = Auth::id();
	$receiver = UOJRequest::user(UOJRequest::GET, 'conversationName');
	$page_num = UOJRequest::uint(UOJRequest::GET, 'pageNumber');
	if (!$receiver || $receiver['username'] === $username) {
		return '[]';
	}
	if (!$page_num) { // false, null, or zero
		return '[]';
	}

	DB::update([
		"update user_msg",
		"set", ["read_time" => DB::now()],
		"where", [
			"sender" => $receiver['username'],
			"receiver" => $username,
			"read_time" => null
		]
	]);

	$result = DB::selectAll([
		"select * from user_msg",
		"where", DB::lor([
			DB::land([
				"sender" => $username,
				"receiver" => $receiver['username']
			]),
			DB::land([
				"sender" => $receiver['username'],
				"receiver" => $username
			])
		]),
		"order by send_time DESC", DB::limit(($page_num - 1) * 10, 11)
	]);
	$ret = [];
	foreach ($result as $msg) {
		$ret[] = [
			$msg['message'], $msg['send_time'], $msg['read_time'], $msg['id'], ($msg['sender'] === $username)
		];
	}
	return json_encode($ret);
}

if (isset($_POST['user_msg'])) {
	die(handleMsgPost());
} elseif (isset($_GET['getConversations'])) {
	die(getConversations());
} elseif (isset($_GET['getHistory'])) {
	die(getHistory());
}
?>

<?php echoUOJPageHeader('私信') ?>

<h1 class="page-header">私信</h1>

<div id="conversations">
</div>

<div id="history" style="display:none">
	<div class="panel panel-primary">
		<div class="panel-heading">
			<button type="button" id="goBack" class="btn btn-info btn-xs" style="position:absolute">返回</button>
			<div id="conversation-name" class="text-center"></div>
		</div>
		<div class="panel-body">
			<ul class="pager top-buffer-no">
				<li class="previous"><a href="#" id="pageLeft">&larr; 更早的消息</a></li>
				<li class="text-center" id="pageShow" style="line-height:32px"></li>
				<li class="next"><a href="#" id="pageRight">更新的消息 &rarr;</a></li>
			</ul>
			<div id="history-list" style="min-height: 200px;">
			</div>
			<ul class="pager bot-buffer-no">
				<li class="previous"><a href="#history" id="pageLeft2">&larr; 更早的消息</a></li>
				<li class="next"><a href="#history" id="pageRight2">更新的消息 &rarr;</a></li>
			</ul>
			<hr />
			<form id="form-message">
				<div class="form-group" id="form-group-message">
					<textarea id="input-message" class="form-control"></textarea>
					<span id="help-message" class="help-block"></span>
				</div>
				<div class="text-right">
					<button type="submit" id="message-submit" class="btn btn-info btn-md">发送</button>
				</div>
			</form>
		</div>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function() {
	$.ajaxSetup({async:false});
	refreshConversations();
	<?php if ($enter !== false): ?>
	enterConversation("<?= $enter['username'] ?>");
	<?php endif ?>
});
</script>

<script type="text/javascript">
function addButton(conversationName, send_time, type) {
	$("#conversations").append(
		'<div class="row top-buffer-sm">' +
			'<div class="col-sm-3">' +
				'<button type="button" class="btn btn-' + ( type ? 'warning' : 'primary' ) + ' btn-block" ' +
					'onclick="enterConversation(\'' + conversationName + '\')">' +
					conversationName +
				'</button>' +
			'</div>' +
			'<div class="col-sm-9" style="line-height:34px">' +
				'最后发送时间：' + send_time +
			'</div>' +
		'</div>'
	);
}

function addBubble(content, send_time, read_time, msgId, conversation, page, type) {
	$("#history-list").append(
		'<div style=' + (type ? "margin-left:0%;margin-right:20%;" : "margin-left:20%;margin-right:0%;") + '>' +
			'<div class="panel panel-info">' +
				'<div class="panel-body" style="background:#afeeee; word-break: break-all">' +
					'<div style="white-space:pre-wrap">' +
						htmlspecialchars(content) +
					'</div>' +
				'</div>' +
				'<div>' +
					'<div class="row">' +
						'<div class="col-sm-6">' +
							'发送时间：' + send_time +
						'</div>' +
						'<div class="col-sm-6 text-right">' +
								'查看时间：' + (read_time == null ? '<strong>未查看</strong>' : read_time) +
						'</div>' +
					'</div>' +
				'</div>' +
			'</div>' +
		'</div>'
	);
}

function submitMessagePost(conversationName) {
	if ($('#input-message').val().length == 0  ||  $('#input-message').val().length >= 65536) {
		$('#help-message').text('私信长度必须在1~65535之间。');
		$('#form-group-message').addClass('has-error');
		return;
	}
	$('#help-message').text('');
	$('#form-group-message').removeClass('has-error');

	$.post('/user/msg', {
		user_msg : 1,
		receiver : conversationName,
		message : $('#input-message').val()
    }, function(msg) {
		$('#input-message').val("");
	});
}

function refreshHistory(conversation, page) {
	$("#history-list").empty();
	var ret = false;
	$('#conversation-name').text(conversation);
	$('#pageShow').text("第" + page.toString() + "页");
	$.get('/user/msg', {
			getHistory : '',
			conversationName : conversation,
			pageNumber : page
		}, function(msg) {
			var result = JSON.parse(msg);
			var cnt = 0, flag = 0, F = 0;
			if (result.length == 11) flag = 1, F = 1;
			result.reverse();
			for (msg in result) {
				if (flag) {
					flag = 0;
					continue;
				}
				var message = result[msg];
				addBubble(message[0], message[1], message[2], message[3], conversation, page, message[4]);
				if ((++cnt) + 1 == result.length  && F) {
					break;
				}
			}
			if (result.length == 11) {
				ret = true;
			}
		}
	);
	return ret;
}

function refreshConversations() {
	$("#conversations").empty();
    $.get('/user/msg', {
			getConversations : ""
		}, function(msg) {
			var result = JSON.parse(msg);
			for (i in result) {
				var conversation = result[i];
				if (conversation[1] == 1) {
					addButton(conversation[2], conversation[0], conversation[1]);
				}
			}
			for (i in result) {
				var conversation = result[i];
				if (conversation[1] == 0) {
					addButton(conversation[2], conversation[0], conversation[1]);
				}
			}
		}
	);
}

function enterConversation(conversationName) {
	var slideTime = 300;
	var page = 1;
	$("#conversations").hide(slideTime);
    var changeAble = refreshHistory(conversationName, page);
	$("#history").slideDown(slideTime);
	$('#form-message').unbind("submit").submit(function() {
		submitMessagePost(conversationName);
		page = 1;
		changeAble = refreshHistory(conversationName, page);
		refreshConversations();
		return false;
	});
	$('#goBack').unbind("click").click(function() {
		refreshConversations();
		$("#history").slideUp(slideTime);
		$("#conversations").show(slideTime);
		return;
	});
	$('#pageLeft').unbind("click").click(function() {
		if (changeAble) page++;
		changeAble = refreshHistory(conversationName, page);
		return false;
	});
	$('#pageLeft2').unbind("click").click(function() {
		if (changeAble) page++;
		changeAble = refreshHistory(conversationName, page);
	});
	$('#pageRight').unbind("click").click(function() {
		if (page > 1) page--;
		changeAble = refreshHistory(conversationName, page);
		return false;
	});
	$('#pageRight2').unbind("click").click(function() {
		if (page > 1) page--;
		changeAble = refreshHistory(conversationName, page);
	});
}

</script>

<?php echoUOJPageFooter() ?>
