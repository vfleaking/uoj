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
		becomeMsgPage('This page has expired.');
	}
}
