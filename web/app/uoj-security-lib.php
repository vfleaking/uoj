<?php

function getPasswordToStore($password, $username) {
	return md5($username . $password);
}
function checkPassword($user, $password) {
   	return $user['password'] == md5($user['username'] . $password);
}
function getPasswordClientSalt() {
	return UOJConfig::$data['security']['user']['client_salt'];
}

function crsf_token() {
	if (!isset($_SESSION['_token'])) {
		$_SESSION['_token'] = uojRandString(60);
	}
	return $_SESSION['_token'];
}
function crsf_check() {
	if (!isset($_SESSION['_token'])) {
		return false;
	}
	
	if (isset($_POST['_token'])) {
		$_token = $_POST['_token'];
	} else if (isset($_GET['_token'])) {
		$_token = $_GET['_token'];
	} else {
		return false;
	}
	return $_token === $_SESSION['_token'];
}
function crsf_defend() {
	if (!crsf_check()) {
		UOJResponse::page403('页面已过期（可能页面真的过期了，也可能是刚才你访问的网页没有完全加载，也可能是你的浏览器版本太老）');
	}
}

function recaptcha_available() {
	return isset(UOJConfig::$data['security']['recaptcha']['site_key'])
		&& isset(UOJConfig::$data['security']['recaptcha']['secret_key']);
}

function recaptcha_sitekey() {
	return UOJConfig::$data['security']['recaptcha']['site_key'];
}

function recaptcha_check() {
	if (!isset($_POST['g-recaptcha-response'])) {
		return false;
	}
	$gresponse = $_POST['g-recaptcha-response'];
	
	$recaptcha = new \ReCaptcha\ReCaptcha(
		UOJConfig::$data['security']['recaptcha']['secret_key'],
		new \ReCaptcha\RequestMethod\Post('https://www.recaptcha.net/recaptcha/api/siteverify')
	);
	// the threshold was 0.5, but some users have reported that they cannot pass the test...
	// common cases: using VPN, or accessing UOJ from a computer lab at school
	$resp = $recaptcha->setScoreThreshold(0.3)
	                  ->verify($gresponse, UOJContext::remoteAddr());
	
	if ($resp->isSuccess()) {
		return true;
	}
	
	if (in_array(\ReCaptcha\ReCaptcha::E_CONNECTION_FAILED, $resp->getErrorCodes())) {
		UOJLog::error('cannot connect to recaptcha!!!');
	}
	return false;
}

function submission_frequency_check() {
	$recent = clone UOJTime::$time_now;
	$recent->sub(new DateInterval("PT1S"));
	$num = DB::selectCount([
		"select count(*) from submissions",
		"where", [
			"submitter" => Auth::id(),
			["submit_time", ">=", $recent->format('Y-m-d H:i:s')]
		]
	]);
	if ($num >= 1) {
		return false;
	}

	// use the implementation below if OJ is under attack
	/*
	$recent = clone UOJTime::$time_now;
	$recent->sub(new DateInterval("PT3S"));
	$num = DB::selectCount([
		"select count(*) from submissions",
		"where", [
			"submitter" => Auth::id(),
			["submit_time", ">=", $recent->format('Y-m-d H:i:s')]
		]
	]);
	if ($num >= 1) {
		return false;
	}
	
	$recent = clone UOJTime::$time_now;
	$recent->sub(new DateInterval("PT1M"));
	$num = DB::selectCount([
		"select count(*) from submissions",
		"where", [
			"submitter" => Auth::id(),
			["submit_time", ">=", $recent->format('Y-m-d H:i:s')]
		]
	]);
	if ($num >= 6) {
		return false;
	}
	
	$recent = clone UOJTime::$time_now;
	$recent->sub(new DateInterval("PT30M"));
	$num = DB::selectCount([
		"select count(*) from submissions",
		"where", [
			"submitter" => Auth::id(),
			["submit_time", ">=", $recent->format('Y-m-d H:i:s')]
		]
	]);
	if ($num >= 30) {
		return false;
	}
	*/
	
	return true;
}
