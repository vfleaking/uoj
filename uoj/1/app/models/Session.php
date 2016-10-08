<?php

class Session {
	public static function init() {
		$domain = UOJConfig::$data['web']['domain'];
		if (validateIP($domain)) {
			$domain = '';
		} else {
			$domain = '.'.$domain;
		}
		
		session_name('UOJSESSID');
		ini_set('session.cookie_path', '/');
		ini_set('session.cookie_domain', $domain);
		
		session_start();
		
		register_shutdown_function(function() {
			if (empty($_SESSION)) {
				session_unset();
				session_destroy();
			}
		});
	}
}
