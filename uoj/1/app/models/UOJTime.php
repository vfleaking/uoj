<?php

class UOJTime {
	public static $time_now;
	public static $time_now_str;
	
	public static function init() {
		self::$time_now = new DateTime();
		self::$time_now_str = self::$time_now->format('Y-m-d H:i:s');
	}
}
