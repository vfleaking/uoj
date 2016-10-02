<?php

class UOJLocale {
	public static $supported_locales = array('zh-cn', 'en');
	public static $supported_modules = array('basic', 'contests', 'problems');
	public static $data = array();
	public static $required = array();
	
	public static function locale() {
		$locale = Cookie::get('uoj_locale');
		if ($locale != null && !in_array($locale, self::$supported_locales)) {
			$locale = null;
			Cookie::unsetVar('uoj_locale', '/');
		}
		if ($locale == null) {
			$locale = 'zh-cn';
		}
		return $locale;
	}
	public static function setLocale($locale) {
		if (!in_array($locale, self::$supported_locales)) {
			return false;
		}
		return Cookie::set('uoj_locale', $locale, time() + 60 * 60 * 24 * 365 * 10, '/');
	}
	public static function requireModule($name) {
		if (in_array($name, self::$required)) {
			return;
		}
		$required[] = $name;
		$data = include($_SERVER['DOCUMENT_ROOT'].'/app/locale/'.$name.'/'.self::locale().'.php');
		
		$pre = $name == 'basic' ? '' : "$name::";
		foreach ($data as $key => $val) {
			self::$data[$pre.$key] = $val;
		}
	}
	public static function get($name) {
		if (!isset(self::$data[$name])) {
			$module_name = strtok($name, '::');
			if (!in_array($module_name, self::$supported_modules)) {
				return false;
			}
			self::requireModule($module_name);
		}
		if (!isset(self::$data[$name])) {
			return false;
		}
		if (is_string(self::$data[$name])) {
			return self::$data[$name];
		} else {
			return call_user_func_array(self::$data[$name], array_slice(func_get_args(), 1));
		}
	}
}
