<?php

class HTML {
	public static function escape($str) {
		return htmlspecialchars($str);
	}
	public static function stripTags($str) {
		return strip_tags($str);
	}
	public static function avatar_addr($user, $size) {
		return '//cn.gravatar.com/avatar/' . md5(strtolower(trim($user['email']))) . "?d=mm&amp;s=$size";
	}
		
	public static function tablist($tabs_info, $cur, $type = 'nav-tabs') {
		$html = '<ul class="nav '.$type.'" role="tablist">';
		foreach ($tabs_info as $id => $tab) {
			$html .= '<li'.($cur == $id ? ' class="active"' : '').'><a href="'.$tab['url'].'" role="tab">'.$tab['name'].'</a></li>';
		}
		$html .= '</ul>';
		return $html;
	}
	
	public static function hiddenToken() {
		return '<input type="hidden" name="_token" value="'.crsf_token().'" />';
	}
	public static function div_vinput($name, $type, $label_text, $default_value) {
		return '<div id="'."div-$name".'">'
			. '<label for="'."input-$name".'" class="control-label">'.$label_text.'</label>'
			. '<input type="'.$type.'" class="form-control" name="'.$name.'" id="'."input-$name".'" value="'.HTML::escape($default_value).'" />'
			. '<span class="help-block" id="'."help-$name".'"></span>'
			. '</div>';
	}
	public static function div_vtextarea($name, $label_text, $default_value) {
		return '<div id="'."div-$name".'">'
			. '<label for="'."input-$name".'" class="control-label">'.$label_text.'</label>'
			. '<textarea class="form-control" name="'.$name.'" id="'."input-$name".'">'.HTML::escape($default_value).'</textarea>'
			. '<span class="help-block" id="'."help-$name".'"></span>'
			. '</div>';
	}
	public static function checkbox($name, $default_value) {
		$status = $default_value ? 'checked="checked" ' : '';
		return '<input type="checkbox" id="'."input-$name".'" name="'.$name.'" '.$status.'/>';
	}
	
	public static function blog_url($username, $uri) {
		$port = UOJConfig::$data['web']['blog']['port'] == 80 ? '' : (':'.UOJConfig::$data['web']['blog']['port']);
		if (UOJConfig::$data['switch']['blog-use-subdomain'])
			$url = UOJConfig::$data['web']['blog']['protocol'].'://'.blog_name_encode($username).'.'.UOJConfig::$data['web']['blog']['host'].$port;
		else
			$url = UOJConfig::$data['web']['blog']['protocol'].'://'.UOJConfig::$data['web']['blog']['host'].$port.'/blogof/'.blog_name_encode($username);
		$url .= $uri;
		$url = rtrim($url, '/');
		return HTML::escape($url);
	}
	
	public static function url($uri, $config = array()) {
		$config = array_merge(array(
			'location' => 'main',
			'params' => null
		), $config);
		
		$path = strtok($uri, '?');
		$qs = strtok('?');
		parse_str($qs, $param);
		
		if ($config['params'] != null) {
			$param = array_merge($param, $config['params']);
		}
		
		$url = UOJConfig::$data['web'][$config['location']]['protocol'].'://'.UOJConfig::$data['web'][$config['location']]['host'];
		if (UOJConfig::$data['web'][$config['location']]['port'] != 80) {
			$url .= ':'.UOJConfig::$data['web'][$config['location']]['port'];
		}
		if ($param) {
			$url .= $path.'?'.HTML::query_string_encode($param);
		} else {
			$url .= rtrim($path, '/');
		}
		return HTML::escape($url);
	}
	public static function timeanddate_url($time, $config = array()) {
		$url = UOJConfig::$data['web']['blog']['protocol'].'://';
		$url .= 'www.timeanddate.com/worldclock/fixedtime.html';
		$url .= '?'.'iso='.$time->format('Ymd\THi');
		$url .= '&'.'p1=33';
		if (isset($config['duration']) && $config['duration'] < 3600) {
			$url .= '&'.'ah='.floor($config['duration'] / 60);
			if ($config['duration'] % 60 != 0) {
				$url .= '&'.'am='.($config['duration'] % 60);
			}
		}
		$url = HTML::escape($url);
		return $url;
	}
	
	public static function js_src($uri, $config = array('location' => 'main')) {
		return '<script src="'.HTML::url($uri, $config).'"></script>';
	}
	public static function css_link($uri, $config = array('location' => 'main')) {
		return '<link type="text/css" rel="stylesheet" href="'.HTML::url($uri, $config).'" />';
	}
	
	public static function query_string_encode($q, $array_name = null) {
		if (!is_array($q)) {
			return false;
		}
		$r = array();
		foreach ((array)$q as $k => $v) {
			if ($array_name !== null) {
				if(is_numeric($k)) {
					$k = $array_name."[]";
				} else {
					$k = $array_name."[$k]";
				}
			}
			if (is_array($v) || is_object($v)) {
				$r[] = self::query_string_encode($v, $k);
			} else {
				$r[] = urlencode($k)."=".urlencode($v);
			}
		}
		return implode("&", $r);
	}
	
	public static function pruifier() {
		include_once $_SERVER['DOCUMENT_ROOT'] . '/app/vendor/htmlpurifier/HTMLPurifier.auto.php';
		$config = HTMLPurifier_Config::createDefault();
		//$config->set('HTML.Doctype', 'HTML 4.01 Transitional');
		$config->set('Cache.DefinitionImpl', null);
		$def = $config->getHTMLDefinition(true);
		$def->addAttribute('span', 'data-rating', 'Number');
		
		$def->addElement('section', 'Block', 'Flow', 'Common');
		$def->addElement('nav',     'Block', 'Flow', 'Common');
		$def->addElement('article', 'Block', 'Flow', 'Common');
		$def->addElement('aside',   'Block', 'Flow', 'Common');
		$def->addElement('header',  'Block', 'Flow', 'Common');
		$def->addElement('footer',  'Block', 'Flow', 'Common');
		
		return new HTMLPurifier($config);
	}
}
