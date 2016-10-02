<?php

class UOJConfig {
	public static $data;
}

if (is_file($_SERVER['DOCUMENT_ROOT'].'/app/.config.php')) {
	UOJConfig::$data = include $_SERVER['DOCUMENT_ROOT'].'/app/.config.php';
} else {
	UOJConfig::$data = include $_SERVER['DOCUMENT_ROOT'].'/app/.default-config.php';
}
