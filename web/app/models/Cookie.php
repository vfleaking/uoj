<?php

class Cookie {
	public static function checksum($key, $val) {
		return md5(UOJConfig::$data['security']['cookie']['checksum_salt'][0] . $key . UOJConfig::$data['security']['cookie']['checksum_salt'][1] . $val . UOJConfig::$data['security']['cookie']['checksum_salt'][2]);
	}
	
	public static function get($key) {
		return isset($_COOKIE[$key]) ? $_COOKIE[$key] : null;
	}
	public static function set($key, $val, $expire = 0, $path = null, $config = array()) {
		$config = array_merge(array(
			'secure' => false,
			'httponly' => false
		), $config);
		$_COOKIE[$key] = $val;
		return setcookie($key, $val, $expire, $path, UOJContext::cookieDomain(), $config['secure'], $config['httponly']);
	}
	public static function unsetVar($key, $path = null) {
		if (!isset($_COOKIE[$key])) {
			return true;
		}
		unset($_COOKIE[$key]);
		return setcookie($key, null, -1, $path, UOJContext::cookieDomain());
	}
	public static function safeCheck($key, $path = null) {
		if (!isset($_COOKIE[$key])) {
			return;
		}
		if (!isset($_COOKIE[$key . '_checksum']) || $_COOKIE[$key . '_checksum'] !== Cookie::checksum($key, $_COOKIE[$key])) {
			Cookie::safeUnset($key, $path);
		}
	}
	public static function safeUnset($key, $path = null) {
		Cookie::unsetVar($key, $path);
		Cookie::unsetVar($key . '_checksum', $path);
	}
	public static function safeGet($key, $path = null) {
		Cookie::safeCheck($key, $path);
		return Cookie::get($key);
	}
	public static function safeSet($key, $val, $expire = 0, $path = null, $config = array()) {
		Cookie::set($key, $val, $expire, $path, $config);
		Cookie::set($key . '_checksum', Cookie::checksum($key, $val), $expire, $path, $config);
	}
}
