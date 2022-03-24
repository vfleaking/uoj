<?php

class Session {
	public static function init() {
		session_name('UOJSESSID');
		ini_set('session.cookie_path', '/');
		ini_set('session.cookie_domain', UOJContext::cookieDomain());
		
		session_start();
		
		register_shutdown_function(function() {
			if (empty($_SESSION)) {
				session_unset();
				session_destroy();
			}
		});
	}
}
