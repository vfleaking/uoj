<?php

class UOJRequest {
	
	const GET = 'get';
	const POST = 'post';

	public static function get($name, $val=null, $default=null) {
		if (!isset($_GET[$name])) {
			return $default;
		}
		if ($val !== null && !$val($_GET[$name])) {
			return $default;
		}
		return $_GET[$name];
	}

	public static function post($name, $val=null, $default=null) {
		if (!isset($_POST[$name])) {
			return $default;
		}
		if ($val !== null && !$val($_POST[$name])) {
			return $default;
		}
		return $_POST[$name];
	}

	public static function data(string $method, string $name, callable $val=null, $default=null) {
		if ($method == self::GET) {
			return self::get($name, $val, $default);
		} elseif ($method == self::POST) {
			return self::post($name, $val, $default);
		} else {
			return null;
		}
	}

	/**
	 * 
	 * no such field: false
	 * invalid user: null
	 * valid user: array
	 * 
	 * @return array|null|false
	 */
	public static function user(string $method, string $key) {
		$username = self::data($method, $key);
		if ($username === null) {
			return false;
		}
		return UOJUser::query($username);
	}

	public static function uint(string $method, string $key) {
		$val = self::data($method, $key);
		if ($val === null) {
			return false;
		}
		return validateUInt($val) ? (int)$val : null;
	}

	public static function option(string $method, string $key, array $options) {
		$str = self::data($method, $key);
		if ($str === null || !in_array($str, $options, true)) {
			return false;
		}
		return $str;
	}
}
