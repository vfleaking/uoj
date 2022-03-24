<?php

if (!isset($_SERVER)) {
    die('Error! Please try again later.');
}

require $_SERVER['DOCUMENT_ROOT'] . '/app/uoj-lib.php';

require UOJContext::documentRoot().'/app/route.php';
require UOJContext::documentRoot().'/app/controllers/subdomain/blog/route.php';

include UOJContext::documentRoot().'/app/controllers'.call_user_func(function() {
	$route = Route::dispatch();
	$q_pos = strpos($route['action'], '?');
	
	if ($q_pos === false) {
		$path = $route['action'];
	} else {
		parse_str(substr($route['action'], $q_pos + 1), $vars);
		$_GET += $vars;
		$path = substr($route['action'], 0, $q_pos);
	}
	
	if (isset($route['onload'])) {
		call_user_func($route['onload']);
	}
	
	return $path;
});
