<?php

class UOJConfig {
	public static $data;
	
	public static function mergeConfig($extra) {
		mergeConfig(self::$data, $extra);
	}
}

if (is_file($_SERVER['DOCUMENT_ROOT'].'/app/.config.php')) {
	UOJConfig::$data = include $_SERVER['DOCUMENT_ROOT'].'/app/.config.php';
	UOJConfig::mergeConfig(include $_SERVER['DOCUMENT_ROOT'].'/app/.default-config.php');
} else {
	UOJConfig::$data = include $_SERVER['DOCUMENT_ROOT'].'/app/.default-config.php';
}
