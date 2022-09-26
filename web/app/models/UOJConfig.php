<?php

class UOJConfig {
	public static $data;
	
	public static function mergeConfig($extra) {
		mergeConfig(self::$data, $extra);
	}

	/**
	 * Perform a sanity check for the config
	 * UOJ does not perform this check by itself
	 * Please run this function manually with the assertion functionality enabled
	 */
	public static function sanityCheck() {
		assert(isset(UOJConfig::$data['web']));

		assert(isset(UOJConfig::$data['web']['main']));
		$sub = UOJConfig::$data['web']['main'];
		assert(isset($sub['host']));
		assert(strpos($sub['host'], ':') === false);
		if (strpos($sub['host'], ':') !== false) {
			die('error!');
		}
		if (isset($sub['protocol'])) {
			assert(in_array($sub['protocol'], ['http', 'https', 'http/https']));
		}
		if (isset($sub['port'])) {
			assert(!array_filter(explode('/', $sub['port']), function($x) { return !validateUInt($x); }));
		}

		assert(isset(UOJConfig::$data['web']['blog']));
		$sub = UOJConfig::$data['web']['blog'];
		assert(isset($sub['host']));
		assert(strpos($sub['host'], ':') === false);
		if (isset($sub['protocol'])) {
			assert(in_array($sub['protocol'], ['http', 'https', 'http/https']));
		}
		if (isset($sub['port'])) {
			assert(!array_filter(explode('/', $sub['port']), function($x) { return !validateUInt($x); }));
		}
	}
}

if (is_file($_SERVER['DOCUMENT_ROOT'].'/app/.config.php')) {
	UOJConfig::$data = include $_SERVER['DOCUMENT_ROOT'].'/app/.config.php';
	UOJConfig::mergeConfig(include $_SERVER['DOCUMENT_ROOT'].'/app/.default-config.php');
} else {
	UOJConfig::$data = include $_SERVER['DOCUMENT_ROOT'].'/app/.default-config.php';
}
