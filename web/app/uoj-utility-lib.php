<?php

function mergeConfig(&$config, $default_config) {
	foreach($default_config as $key => $val) {
		if (!isset($config[$key])) {
			$config[$key] = $val;
		} elseif (is_assoc($config[$key])) {
			mergeConfig($config[$key], $val);
		}
	}
}

function is_assoc($arr) {
    if (!is_array($arr)) {
        return false;
    }
    foreach(array_keys($arr) as $key) {
        if (!is_int($key)) {
            return true;
        }
    }
    return false;
}

/**
 * Check whether an array is a list.
 * Delete this function when using PHP 8.1
 */
function array_is_list($arr) {
	if ($arr === []) {
		return true;
	}
	return array_keys($arr) === range(0, count($arr) - 1);
}

function strStartWith($str, $pre) {
	return substr($str, 0, strlen($pre)) === $pre;
}

function strEndWith($str, $suf) {
	return substr($str, -strlen($suf)) === $suf;
}

function strOmit($str, $len) {
	if (strlen($str) <= $len + 3) {
		return $str;
	} else {
		return substr($str, 0, $len) . '...';
	}
}

function uojTextEncode($str, $config = array()) {
	mergeConfig($config, [
		'allow_CR' => false,
		'html_escape' => false
	]);
	
	$allow = array();
	for ($c = 32; $c <= 126; $c++) {
		$allow[chr($c)] = true;
	}
	$allow["\n"] = true;
	$allow[" "] = true;
	$allow["\t"] = true;
	
	if ($config['allow_CR']) {
		$allow["\r"] = true;
	}
	
	$len = strlen($str);
	$ok = true;
	for ($i = 0; $i < $len; $i++) {
		$c = $str[$i];
		if (!isset($allow[$c])) {
			$ok = false;
		}
	}
	if ($ok && mb_check_encoding($str, 'utf-8')) {
		if (!$config['html_escape']) {
			return $str;
		} else {
			return HTML::escape($str);
		}
	} else {
		$len = strlen($str);
		$res = '';
		$i = 0;
		while ($i < $len) {
			$c = $str[$i];
			if (ord($c) < 128) {
				if (isset($allow[$c])) {
					if ($config['html_escape']) {
						if ($c == '&') {
							$res .= '&amp;';
						} else if ($c == '"') {
							$res .= '&quot;';
						} else if ($c == '<') {
							$res .= '&lt;';
						} else if ($c == '>') {
							$res .= '&gt;';
						} else {
							$res .= $c;
						}
					} else {
						$res .= $c;
					}
				} else {
					$res .= '<b>\x' . bin2hex($c) . '</b>';
				}
				$i++;
			} else {
				$ok = false;
				$cur = $c;
				for ($j = $i + 1; $j < $i + 4 && $j < $len; $j++) {
					$cur .= $str[$j];
					if (mb_check_encoding($cur, 'utf-8')) {
						$ok = true;
						break;
					}
				}
				if ($ok) {
					$res .= $cur;
					$i = $j + 1;
				} else {
					$res .= '<b>\x' . bin2hex($c) . '</b>';
					$i++;
				}
			}
		}
		return $res;
	}
}

function base64url_encode($data) { 
	return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); 
}
function base64url_decode($data) { 
	return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT)); 
}

function blog_name_encode($name) {
	$name = str_replace('-', '_', $name);
	if (!strStartWith($name, '_') && !strEndWith($name, '_')) {
		$name = str_replace('_', '-', $name);
	}
	$name = strtolower($name);
	return $name;
}
function blog_name_decode($name) {
	$name = str_replace('-', '_', $name);
	$name = strtolower($name);
	return $name;
}

function camelize($str, $delimiters = '-_') {
	$str = ucwords($str, $delimiters);
	foreach (str_split($delimiters) as $c) {
		$str = str_replace($c, '', $str);
	}
	return $str;
}

function isSuperUser($user) {
	return $user != null && $user['usergroup'] == 'S';
}
function isTmpUser($user) {
	return $user != null && $user['usergroup'] == 'T';
}

function sendSystemMsg($username, $title, $content) {
	DB::insert([
        "insert into user_system_msg",
        "(receiver, title, content, send_time)",
        "values", DB::tuple([$username, $title, $content, DB::now()])
    ]);
}

function retry_loop(callable $f, $retry = 5, $ms = 10) {
	for ($i = 0; $i < $retry; $i++) {
		$ret = $f();
		if ($ret !== false) {
			return $ret;
		}
		usleep($ms * 1000);
	}
	return $ret;
}