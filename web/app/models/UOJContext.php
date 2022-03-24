<?php

class UOJContext {
    public static $meta_default = [
		'active_duration_M' => 36
	];

	public static $data = [
		'type' => 'main'
	];
	
	public static function pageConfig() {
		switch (self::$data['type']) {
			case 'main':
				return [
					'PageNav' => 'main-nav'
				];
			case 'blog':
				return [
					'PageNav' => 'blog-nav',
					'PageMainTitle' => UOJUserBlog::id() . '的博客',
					'PageMainTitleOnSmall' => '博客',
				];
		}
	}
	
	public static function isAjax() {
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
	}
	
	public static function contentLength() {
		if (!isset($_SERVER['CONTENT_LENGTH'])) {
			return null;
		}
		return (int)$_SERVER['CONTENT_LENGTH'];
	}
	
	public static function documentRoot() {
		return $_SERVER['DOCUMENT_ROOT'];
	}
	public static function storagePath() {
		return $_SERVER['DOCUMENT_ROOT'].'/app/storage';
	}
	public static function remoteAddr() {
		return $_SERVER['REMOTE_ADDR'];
	}
	public static function httpXForwardedFor() {
		return isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
	}
	public static function httpUserAgent() {
		return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	}
	public static function requestURI() {
		return $_SERVER['REQUEST_URI'];
	}
	public static function requestPath() {
		$uri = $_SERVER['REQUEST_URI'];
		$p = strpos($uri, '?');
		if ($p === false) {
			return $uri;
		} else {
			return substr($uri, 0, $p);
		}
	}
	public static function requestMethod() {
		return $_SERVER['REQUEST_METHOD'];
	}
	public static function httpHost() {
		return isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
	}
	public static function isUsingHttps() {
		return (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' ||  $_SERVER['HTTPS'] == 1))
			|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
			|| $_SERVER['SERVER_PORT'] == 443;
	}
	public static function cookieDomain() {
		$domain = UOJConfig::$data['web']['domain'];
		if ($domain === null) {
			$domain = UOJConfig::$data['web']['main']['host'];
		}
		if (validateIP($domain)) {
			$domain = '';
		} else {
			$domain = '.'.$domain;
		}
		return $domain;
	}

	public static function hasCDN() {
		return isset(UOJConfig::$data['web']['cdn']);
	}

	public static function type() {
		return self::$data['type'];
	}
	
	public static function setupBlog() {
		UOJUserBlog::init();
		self::$data['type'] = 'blog';
	}

	public static function getMeta($name) {
		$value = DB::selectFirst([
			"select value from meta",
			"where", ['name' => $name]
		]);
		if ($value === null) {
			return self::$meta_default[$name];
		} else {
			return json_decode($value['value'], true);
		}
	}

	public static function setMeta($name, $value) {
		$value = json_encode($value);
		return DB::update([
			"insert into meta", DB::bracketed_fields(['name', 'value', 'updated_at']),
			"values", DB::tuple([$name, $value, DB::now()]),
			"on duplicate key update", ['value' => $value]
		]);
	}
}
