<?php

class Route {
	protected static $routes = array();
	protected static $patterns = array();
	protected static $groupStack = array(array());
	
	public static function match($methods, $uri, $action) {
		return self::addRoute(array_map('strtoupper', (array)$methods), $uri, $action);
	}
	public static function any($uri, $action) {
		return self::addRoute(array('GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'), $uri, $action);
	}
	public static function get($uri, $action) {
		return self::addRoute(['GET', 'HEAD'], $uri, $action);
	}
	public static function post($uri, $action) {
		return self::addRoute('POST', $uri, $action);
	}
	public static function put($uri, $action) {
		return self::addRoute('PUT', $uri, $action);
	}
	public static function patch($uri, $action) {
		return self::addRoute('PATCH', $uri, $action);
	}
	public static function delete($uri, $action) {
		return self::addRoute('DELETE', $uri, $action);
	}
	
	public static function group(array $attributes, Closure $callback) {
		self::$groupStack[] = array_merge(self::getGroup(), $attributes);
		call_user_func($callback);
		array_pop(self::$groupStack);
	}
	public static function getGroup() {
		return self::$groupStack[count(self::$groupStack) - 1];
	}
	
	public static function pattern($name, $pat) {
		self::$patterns[$name] = $pat;
	}
	
	public static function dispatch() {
		foreach (self::$routes as $route) {
			if (self::checkRoute($route)) {
				return $route;
			}
		}
		become404Page();
	}
	
	protected static function addRoute($methods, $uri, $action) {
		if (is_string($methods)) {
			$methods = [$methods];
		}
		
		$cur = array();
		$cur['methods'] = $methods;
		$cur['uri'] = rtrim($uri, '/');
		$cur['action'] = $action;
		$cur = array_merge(self::getGroup(), $cur);
		self::$routes[] = $cur;
		return $cur;
	}
	protected static function checkRoute($route) {
		if (!in_array(UOJContext::requestMethod(), $route['methods'])) {
			return false;
		}
		
		$rep_arr = array();
		foreach (self::$patterns as $name => $pat) {
			$rep_arr['{'.$name.'}'] = "(?P<$name>$pat)";
		}
		$rep_arr['/'] = '\/';
		$rep_arr['.'] = '\.';
		
		$matches = array();
		if (isset($route['domain'])) {
			$domain_pat = strtr($route['domain'], $rep_arr);
			if (!preg_match('/^'.$domain_pat.'$/', UOJContext::httpHost(), $domain_matches)) {
				return false;
			}
			$matches = array_merge($matches, $domain_matches);
		}
		
		$uri_pat = strtr($route['uri'], $rep_arr);
		if (!preg_match('/^'.$uri_pat.'$/', rtrim(UOJContext::requestPath(), '/'), $uri_matches)) {
			return false;
		}
		$matches = array_merge($matches, $uri_matches);
		
		foreach ($matches as $key => $val) {
			if (!is_numeric($key)) {
				$_GET[$key] = $val;
			}
		}
		
		return true;
	}
}
