<?php

function mergeConfig(&$config, $default_config) {
	foreach($default_config as $key => $val) {
		if (!isset($config[$key])) {
			$config[$key] = $val;
		} elseif (is_array($config[$key])) {
			mergeConfig($config[$key], $val);
		}
	}
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

function isSuperUser($user) {
	return $user != null && $user['usergroup'] == 'S';
}
function getProblemExtraConfig($problem) {
	$extra_config = json_decode($problem['extra_config'], true);
	
	$default_extra_config = array(
		'view_content_type' => 'ALL',
		'view_all_details_type' => 'ALL',
		'view_details_type' => 'ALL'
	);
	
	mergeConfig($extra_config, $default_extra_config);
	
	return $extra_config;
}
function getProblemSubmissionRequirement($problem) {
	return json_decode($problem['submission_requirement'], true);
}
function getProblemCustomTestRequirement($problem) {
	$extra_config = json_decode($problem['extra_config'], true);
	if (isset($extra_config['custom_test_requirement'])) {
		return $extra_config['custom_test_requirement'];
	} else {
		$answer = array(
			'name' => 'answer',
			'type' => 'source code',
			'file_name' => 'answer.code'
		);
		foreach (getProblemSubmissionRequirement($problem) as $req) {
			if ($req['name'] == 'answer' && $req['type'] == 'source code' && isset($req['languages'])) {
				$answer['languages'] = $req['languages'];
			}
		}
		return array(
			$answer,
			array(
				'name' => 'input',
				'type' => 'text',
				'file_name' => 'input.txt'
			)
		);
	}
}

function sendSystemMsg($username, $title, $content) {
	$content = DB::escape($content);
	$title = DB::escape($title);
	DB::insert("insert into user_system_msg (receiver, title, content, send_time) values ('$username', '$title', '$content', now())");
}
