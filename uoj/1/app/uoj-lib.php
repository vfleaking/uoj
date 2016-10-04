<?php
	error_reporting(E_ALL ^ E_NOTICE);
	
	spl_autoload_register(function($class_name) {
		require_once $_SERVER['DOCUMENT_ROOT'] . '/app/models/' . $class_name . '.php';
	});
	
	function requireLib($name) { // html lib
		global $REQUIRE_LIB;
		$REQUIRE_LIB[$name] = '';
	}
	function requirePHPLib($name) { // uoj php lib
		require $_SERVER['DOCUMENT_ROOT'].'/app/uoj-'.$name.'-lib.php';
	}
	
	requirePHPLib('validate');
	requirePHPLib('query');
	requirePHPLib('rand');
	requirePHPLib('utility');
	requirePHPLib('security');
	requirePHPLib('contest');
	requirePHPLib('html');
	
	Session::init();
	UOJTime::init();
	DB::init();
	Auth::init();
	
	if (isset($_GET['locale'])) {
		UOJLocale::setLocale($_GET['locale']);
	}
	UOJLocale::requireModule('basic');
?>
