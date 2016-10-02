<?php
	if (Auth::check()) {
		redirectTo('/');
	}
	
	function handleLoginPost() {
		if (!crsf_check()) {
			return 'expired';
		}
		if (!isset($_POST['username'])) {
			return "failed";
		}
		if (!isset($_POST['password'])) {
			return "failed";
		}
		$username = $_POST['username'];
		$password = $_POST['password'];
		
		if (!validateUsername($username)) {
			return "failed";
		}
		if (!validatePassword($password)) {
			return "failed";
		}
		
		$user = queryUser($username);
		if (!$user || !checkPassword($user, $password)) {
			return "failed";
		}
		
		if ($user['usergroup'] == 'B') {
			return "banned";
		}
		
		Auth::login($user['username']);
		return "ok";
	}
	
	if (isset($_POST['login'])) {
		echo handleLoginPost();
		die();
	}
?>
<?php
	$REQUIRE_LIB['md5'] = '';
?>
<?php echoUOJPageHeader(UOJLocale::get('login')) ?>
<h2 class="page-header"><?= UOJLocale::get('login') ?></h2>
<form id="form-login" class="form-horizontal" method="post">
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
      <span class="help-block" id="help-password"></span>
    </div>
  </div>
  <div class="form-group">
    <div class="col-sm-offset-2 col-sm-3">
      <button type="submit" id="button-submit" class="btn btn-default"><?= UOJLocale::get('submit') ?></button>
    </div>
  </div>
</form>

<script type="text/javascript">
function validateLoginPost() {
	var ok = true;
	ok &= getFormErrorAndShowHelp('username', validateUsername);
	ok &= getFormErrorAndShowHelp('password', validatePassword);
	return ok;
}

function submitLoginPost() {
	if (!validateLoginPost()) {
		return false;
	}
	
	$.post('/login', {
		_token : "<?= crsf_token() ?>",
		login : '',
		username : $('#input-username').val(),
		password : md5($('#input-password').val(), "<?= getPasswordClientSalt() ?>")
	}, function(msg) {
		if (msg == 'ok') {
			var prevUrl = document.referrer;
			if (prevUrl == '' || /.*\/login.*/.test(prevUrl) || /.*\/register.*/.test(prevUrl) || /.*\/reset-password.*/.test(prevUrl)) {
				prevUrl = '/';
			};
			window.location.href = prevUrl;
		} else if (msg == 'banned') {
			$('#div-username').addClass('has-error');
			$('#help-username').html('用户已被禁用。');
		} else if (msg == 'expired') {
			$('#div-username').addClass('has-error');
			$('#help-username').html('页面已过期。');
		} else {
			$('#div-username').addClass('has-error');
			$('#help-username').html('用户名或密码错误。');
			$('#div-password').addClass('has-error');
			$('#help-password').html('用户名或密码错误。<a href="/forgot-password">忘记密码？</a>');
		}
	});
	return true;
}

$(document).ready(function() {
	$('#form-login').submit(function(e) {
		e.preventDefault();
		submitLoginPost();
	});
});

</script>
<?php echoUOJPageFooter() ?>
