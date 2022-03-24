<?php
	function handleRegisterPost() {
		if (!crsf_check()) {
			return '页面已过期';
		}
		if (recaptcha_available() && !recaptcha_check()) {
			return '咦，UOJ 怀疑你是机器人哦……如果真的是人类的话，换个网络重试一下或者找管理员瞧瞧？';
		}
		
		if (!isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['email'])) {
			return "无效表单";
		}
		
		try {
			$user = UOJUser::register([
				'username' => UOJRequest::post('username'),
				'password' => UOJRequest::post('password'),
				'email' => UOJRequest::post('email')
			]);
		} catch (UOJInvalidArgumentException $e) {
			return "失败：".$e->getMessage();
		}
		
		return "欢迎你！" . $user['username'] . "，你已成功注册。";
	}
	
	if (isset($_POST['register'])) {
		echo handleRegisterPost();
		die();
	} elseif (isset($_POST['check_username'])) {
		$username = $_POST['username'];
		if (validateUsername($username) && !UOJUser::query($username)) {
			echo '{"ok" : true}';
		} else {
			echo '{"ok" : false}';
		}
		die();
	}
?>
<?php
	$REQUIRE_LIB['md5'] = '';
	$REQUIRE_LIB['dialog'] = '';
	if (recaptcha_available()) {
		$REQUIRE_LIB['recaptcha'] = '';
	}
?>
<?php echoUOJPageHeader(UOJLocale::get('register')) ?>
<h2 class="page-header"><?= UOJLocale::get('register') ?></h2>
<form id="form-register" class="form-horizontal" method="post">
	<div id="div-email" class="form-group">
		<label for="input-email" class="col-sm-2 control-label"><?= UOJLocale::get('email') ?></label>
		<div class="col-sm-3">
			<input type="email" class="form-control" id="input-email" name="email" placeholder="<?= UOJLocale::get('enter your email') ?>" maxlength="50" />
			<span class="help-block" id="help-email"></span>
		</div>
	</div>
	<div id="div-username" class="form-group">
		<label for="input-username" class="col-sm-2 control-label"><?= UOJLocale::get('username') ?></label>
		<div class="col-sm-3">
			<input type="text" class="form-control" id="input-username" name="username" placeholder="<?= UOJLocale::get('enter your username') ?>" maxlength="20" />
			<span class="help-block" id="help-username"></span>
		</div>
	</div>
	<div id="div-password" class="form-group">
		<label for="input-password" class="col-sm-2 control-label"><?= UOJLocale::get('password') ?></label>
		<div class="col-sm-3">
			<input type="password" class="form-control" id="input-password" name="password" placeholder="<?= UOJLocale::get('enter your password') ?>" maxlength="20" />
			<input type="password" class="form-control top-buffer-sm" id="input-confirm_password" placeholder="<?= UOJLocale::get('re-enter your password') ?>" maxlength="20" />
			<span class="help-block" id="help-password"></span>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-3">
			<?php
				$submit_btn_attrs = [
					'type' => 'submit',
					'id' => 'button-submit',
					'class' => 'btn btn-default'
				];
				if (recaptcha_available()) {
					$submit_btn_attrs['class'] .= ' g-recaptcha';
					$submit_btn_attrs += [
						'data-sitekey' => recaptcha_sitekey(),
						'data-action' => 'submit',
						'data-callback' => 'submitRegisterPost'
					];
				}
				echo HTML::tag('button', $submit_btn_attrs, UOJLocale::get('submit'));
			?>
		</div>
	</div>
</form>

<script type="text/javascript">
function checkUsernameNotInUse() {
	var ok = false;
	$.ajax({
		url : '/register',
		type : 'POST',
		dataType : 'json',
		async : false,
		
		data : {
			check_username : '',
			username : $('#input-username').val()
		},
		success : function(data) {
			ok = data.ok;
		},
		error :	function(XMLHttpRequest, textStatus, errorThrown) {
			alert(XMLHttpRequest.responseText);
			ok = false;
		}
	});
	return ok;
}
function validateRegisterPost() {
	var ok = true;
	ok &= getFormErrorAndShowHelp('email', validateEmail);
	ok &= getFormErrorAndShowHelp('username', function(str) {
		var err = validateUsername(str);
		if (err)
			return err;
		if (!checkUsernameNotInUse())
			return '该用户名已被人使用了。';
		return '';
	})
	ok &= getFormErrorAndShowHelp('password', validateSettingPassword);
	return ok;
}

function submitRegisterPost(gtoken) {
	if (!validateRegisterPost()) {
		return;
	}
	
	$.post('/register', {
		_token : "<?= crsf_token() ?>",
		"g-recaptcha-response": gtoken,
		register : '',
		username : $('#input-username').val(),
		email    : $('#input-email').val(),
		password : md5($('#input-password').val(), "<?= getPasswordClientSalt() ?>")
	}, function(msg) {
		if (/^欢迎你！/.test(msg)) {
			BootstrapDialog.show({
				title	 : '注册成功',
				message : msg,
				type		: BootstrapDialog.TYPE_SUCCESS,
				buttons: [{
					label: '好的',
					action: function(dialog) {
						dialog.close();
					}
				}],
				onhidden : function(dialog) {
					var prevUrl = document.referrer;
					if (!prevUrl) {
						prevUrl = '/';
					};
					window.location.href = prevUrl;
				}
			});
		} else {
			BootstrapDialog.show({
				title	 : '注册失败',
				message : msg,
				type		: BootstrapDialog.TYPE_DANGER,
				buttons: [{
					label: '好的',
					action: function(dialog) {
						dialog.close();
					}
				}],
			});
		}
	});
}
<?php if (!recaptcha_available()): ?>
$(document).ready(function() {
	$('#form-register').submit(function(e) {
		submitRegisterPost(null);
		return false;
	});
});
<?php endif ?>
</script>
<?php echoUOJPageFooter() ?>
