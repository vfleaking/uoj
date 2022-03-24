<?php

class UOJLocale {
	public static $supported_locales = [
		'zh-cn' => [
			'name' => '中文',
			'img' => '//img.uoj.ac/utility/flags/24/cn.png'
		],
		'en' => [
			'name' => 'English',
			'img' => '//img.uoj.ac/utility/flags/24/gb.png'
		]
	];
	public static $supported_modules = ['basic', 'contests', 'problems'];
	public static $data = [];
	public static $required = [];

	public static function init() {
		foreach (self::$supported_locales as $locale => $entry) {
			// the locale switcher uses image inputs (see getLocaleSwitcher below). We just ignore the x and y coordinates.
			if (isset($_POST["set-locale-{$locale}_x"]) && isset($_POST["set-locale-{$locale}_y"])) {
				self::setLocale($locale);
				redirectTo(UOJContext::requestURI());
			}
		}
		self::requireModule('basic');
	}
	
	public static function locale() {
		$locale = Cookie::get('uoj_locale');
		if ($locale != null && !isset(self::$supported_locales[$locale])) {
			$locale = null;
			Cookie::unsetVar('uoj_locale', '/');
		}
		if ($locale == null) {
			$locale = 'zh-cn';
		}
		return $locale;
	}
	public static function setLocale($locale) {
		if (!isset(self::$supported_locales[$locale])) {
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
	public static function get($name, ...$args) {
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
			return call_user_func_array(self::$data[$name], $args);
		}
	}

	public static function getLocaleSwitcher() {
		$html = HTML::tag_begin('form', ['method' => 'post', 'action' => UOJContext::requestURI()]);
		$html .= HTML::tag_begin('ul', ['class' => 'list-inline']);
		foreach (self::$supported_locales as $locale => $entry) {
			$html .= HTML::tag('li', [], 
				HTML::empty_tag('input', ['type' => 'image', 'name' => "set-locale-$locale", 'alt' => $entry['name'], 'src' => $entry['img']])
			);
		}
		$html .= HTML::tag_end('ul');
		$html .= HTML::tag_end('form');
		return $html;
	}
}
