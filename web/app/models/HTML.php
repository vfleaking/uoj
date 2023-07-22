<?php

class HTML {
	public static function escape(?string $str, $cfg = []) {
		if ($str === null) {
			return '';
		} else {
			if (!empty($cfg['single_line'])) {
				$str = str_replace(["\n", "\r"], '', $str);
			}
			return htmlspecialchars($str);
		}
	}
	public static function stripTags(string $str) {
		return strip_tags($str);
	}
	
	public static function protocol(string $loc = 'main') {
		if (UOJConfig::$data['web'][$loc]['protocol'] === 'http/https') {
			if (UOJContext::isUsingHttps()) {
				return 'https'; 
			} else {
				return 'http';
			}
		} else {
			return UOJConfig::$data['web'][$loc]['protocol'];
		}
	}
	public static function port(string $loc = 'main') {
		if (UOJConfig::$data['web'][$loc]['port'] === '80/443') {
			return HTML::standard_port(HTML::protocol($loc));
		} else {
			return UOJConfig::$data['web'][$loc]['port'];
		}
	}
	public static function standard_port(string $protocol) {
		if ($protocol === 'http') {
			return 80;
		} else if ($protocol === 'https') {
			return 443;
		} else {
			return null;
		}
	}

	public static function attr($attr) {
		$html = '';
		foreach ($attr as $key => $val) {
			$html .= ' '.$key.'="';
			$html .= HTML::escape(is_array($val) ? implode(' ', $val) : $val);
			$html .= '"';
		}
		return $html;
	}

	public static function tag_begin(string $name, array $attr = []) {
		return '<'.$name.HTML::attr($attr).'>';
	}
	public static function tag_end(string $name) {
		return '</'.$name.'>';
	}
	public static function tag(string $name, array $attr, $content) {
		return HTML::tag_begin($name, $attr).$content.HTML::tag_end($name);
	}
	public static function empty_tag(string $name, array $attr) {
		return '<'.$name.HTML::attr($attr).' />';
	}

	public static function avatar_addr($user, $size) {
		$extra = UOJUser::getExtra($user);
		if (isset($extra['avatar']) && $extra['avatar'] === 'banned') {
			return '//cravatar.cn/avatar/' . md5('banned') . "?f=mp&amp;s=$size";
		} else {
			return '//cravatar.cn/avatar/' . md5(strtolower(trim($user['email']))) . "?d=mp&amp;s=$size";
		}
	}
	
	public static function tablist(array $tabs_info, $cur, $type = 'nav-tabs') {
		$html = '<ul class="nav '.$type.'" role="tablist">';
		foreach ($tabs_info as $id => $tab) {
			$html .= '<li'.($cur == $id ? ' class="active"' : '').'>';
			$html .= '<a href="'.$tab['url'].'" role="tab">'.$tab['name'].'</a>';
			$html .= '</li>';
		}
		$html .= '</ul>';
		return $html;
    }

	public static function samepage_tablist($tablist_name, array $tabs_info, $cur, $type = 'nav-tabs') {
		$html = '<ul class="nav '.$type.'" role="tablist">';
		foreach ($tabs_info as $id => $tab) {
			$html .= '<li'.($cur == $id ? ' class="active"' : '').'>';

			if (isset($tab['url'])) {
				$html .= '<a href="'.$tab['url'].'" role="tab">'.$tab['name'].'</a>';
			} else {
				$html .= HTML::tag('a', [
					'href' => "#{$tablist_name}-{$id}",
					'role' => 'tab',
					'data-toggle' => $tablist_name
				], $tab['name']);
			}

			$html .= '</li>';
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
	public static function checkbox($name, $default_status = false) {
		$status = $default_status ? 'checked="checked" ' : '';
		return '<input type="checkbox" id="'."input-$name".'" name="'.$name.'" '.$status.'/>';
	}
	public static function checkbox_in_array($name, $value, $default_status = false) {
		$status = $default_status ? 'checked="checked" ' : '';
		return '<input type="checkbox" id="'."input-{$name}-{$value}".'" name="'.$name.'[]" value="'.$value.'" '.$status.'/>';
	}
	public static function radio($name, $value, $default_status = false) {
		$status = $default_status ? 'checked="checked" ' : '';
		return '<input type="radio" id="'."input-{$name}-{$value}".'" name="'.$name.'" value="'.$value.'" '.$status.'/>';
	}
	public static function option($value, $text, $selected) {
		return '<option value="'.HTML::escape($value).'"'
			.($selected ? ' selected="selected"' : '').'>'
			.HTML::escape($text)
			.'</option>';
	}
	public static function tr_none() {
		return '<tr class="text-center"><td colspan="233">'.UOJLocale::get('none').'</td></tr>';
    }
	
	public static function blog_url($username, $uri, array $cfg = []) {
		$cfg += [
			'escape' => true
		];

		$protocol = HTML::protocol('blog');
		$url = $protocol.'://'.blog_name_encode($username).'.'.UOJConfig::$data['web']['blog']['host'];
		if (HTML::port('blog') != HTML::standard_port($protocol)) {
			$url .= ':'.HTML::port('blog');
		}
		$url .= $uri;
		$url = rtrim($url, '/');

		if ($cfg['escape']) {
			$url = HTML::escape($url);
		}
		return $url;
	}
	
	public static function url(string $uri, array $cfg = []) {
		$cfg = array_merge([
			'location' => 'main',
			'params' => null,
			'remove_all_params' => false,
			'with_token' => false,
			'escape' => true
		], $cfg);

		if ($cfg['location'] == 'cdn' && !UOJContext::hasCDN()) {
			$cfg['location'] = 'main';
		}
		
		if (strStartWith($uri, '?')) {
			$path = strtok(UOJContext::requestURI(), '?');
			$qs = strtok($uri, '?');
		} else {
			$path = strtok($uri, '?');
			$qs = strtok('?');
		}
		parse_str($qs, $param);
		if ($cfg['remove_all_params']) {
			$param = [];
		} else if ($cfg['params'] != null) {
			$param = array_merge($param, $cfg['params']);
		}
		if ($cfg['with_token']) {
			$param['_token'] = crsf_token();
		}
		
		$protocol = HTML::protocol($cfg['location']);
		$url = $protocol.'://'.UOJConfig::$data['web'][$cfg['location']]['host'];
		if (HTML::port($cfg['location']) != HTML::standard_port($protocol)) {
			$url .= ':'.HTML::port($cfg['location']);
		}
		if ($param) {
			$url .= $path.'?'.HTML::query_string_encode($param);
		} else {
			$url .= rtrim($path, '/');
		}
		if ($cfg['escape']) {
			$url = HTML::escape($url);
		}
		return $url;
	}
	public static function timeanddate_url(DateTime $time, array $cfg = []) {
		$url = HTML::protocol().'://';
		$url .= 'www.timeanddate.com/worldclock/fixedtime.html';
		$url .= '?'.'iso='.$time->format('Ymd\THi');
		$url .= '&'.'p1=33';
		if (isset($cfg['duration']) && $cfg['duration'] < 3600) {
			$url .= '&'.'ah='.floor($cfg['duration'] / 60);
			if ($cfg['duration'] % 60 != 0) {
				$url .= '&'.'am='.($cfg['duration'] % 60);
			}
		}
		return HTML::escape($url);
	}
    
    public static function link(?string $uri, $text, $cfg = ['location' => 'main']) {
        if ($uri === null) {
            return '<a>'.HTML::escape($text).'</a>';
        }
        return '<a href="'.HTML::url($uri, $cfg).'">'.HTML::escape($text).'</a>';
    }
	public static function autolink(string $url, array $attr = []) {
		return '<a href="'.$url.'"'.HTML::attr($attr).'>'.$url.'</a>';
	}
	public static function js_src(string $uri, array $cfg = []) {
		$cfg += [
			'location' => 'cdn',
			'async' => false
		];
		$async = empty($cfg['async']) ? '' : 'async ';
		return '<script type="text/javascript" '.$async.'src="'.HTML::url($uri, $cfg).'"></script>';
	}
	public static function css_link(string $uri, $cfg = []) {
		$cfg += ['location' => 'cdn'];
		return '<link type="text/css" rel="stylesheet" href="'.HTML::url($uri, $cfg).'" />';
	}

	public static function table($header, iterable $data, $cfg = []) {
		mergeConfig($cfg, [
			'th' => function($c) {
				return "<th>{$c}</th>";
			},
			'td' => function($d) {
				return "<td>{$d}</td>";
			},
			'tr' => false, // if tr is a function, then td and tr_attr is disabled
			'empty' => 'HTML::tr_none',
			'table_attr' => [
				'class' => ['table', 'table-bordered', 'table-hover', 'table-striped', 'table-text-center'],
			],
			'thead_attr' => [],
			'tbody_attr' => [],
			'tr_attr' => function($row, $idx) {
				return [];
			}
		]);

		$html = HTML::tag_begin('table', $cfg['table_attr']);
		$html .= HTML::tag_begin('thead', $cfg['thead_attr']);
		if (is_array($header)) {
			$html .= '<tr>'.implode(' ', array_map($cfg['th'], array_values($header), array_keys($header))).'</tr>';
		} else {
			$html .= $header;
		}
		$html .= HTML::tag_end('thead');
		$html .= HTML::tag_begin('tbody', $cfg['tbody_attr']);
		if (is_iterable($data)) {
			$data_html = [];
			if (is_callable($cfg['tr'])) {
				foreach ($data as $idx => $row) {
					$data_html[] = $cfg['tr']($row, $idx);
				}
			} else {
				foreach ($data as $idx => $row) {
					$data_html[] = HTML::tag_begin('tr', $cfg['tr_attr']($row, $idx));
					if (is_array($row)) {
						foreach ($row as $cidx => $c) {
							$data_html[] = $cfg['td']($c, $cidx);
						}
					} else {
						$data_html[] = $row;
					}
					$data_html[] = HTML::tag_end('tr');
				}
			}
			$data_html = implode($data_html);
		} else {
			$data_html = $data;
		}
		$html .= $data_html !== '' ? $data_html : $cfg['empty']();
		$html .= HTML::tag_end('tbody');
		$html .= HTML::tag_end('table');
		return $html;
	}

	public static function responsive_table($header, $data, $cfg = []) {
		return HTML::tag_begin('div', ['class' => 'table-responsive']).HTML::table($header, $data, $cfg).HTML::tag_end('div');
	}
	
	public static function query_string_encode($q, $array_name = null) {
		if (!is_array($q)) {
			return false;
		}
		$r = [];
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
	
	public static function purifier() {
		$cfg = HTMLPurifier_Config::createDefault();

		$cache_path = UOJContext::storagePath().'/tmp/htmlpurifier';
		if (!is_dir($cache_path)) {
    		mkdir($cache_path, 0755, true);
    	}
		$cfg->set('Cache.SerializerPath', $cache_path);
		$def = $cfg->getHTMLDefinition(true);
		$def->addAttribute('span', 'data-rating', 'Number');
		$def->addAttribute('span', 'data-max', 'Number');
		$def->addAttribute('span', 'data-score', 'Number');
		
		$def->addElement('section', 'Block', 'Flow', 'Common');
		$def->addElement('nav',	 'Block', 'Flow', 'Common');
		$def->addElement('article', 'Block', 'Flow', 'Common');
		$def->addElement('aside',   'Block', 'Flow', 'Common');
		$def->addElement('header',  'Block', 'Flow', 'Common');
		$def->addElement('footer',  'Block', 'Flow', 'Common');
		
		return new HTMLPurifier($cfg);
    }

    public static function echoPanel(string $cls, $title, $body, $other = null) {
        echo '<div class="panel ', $cls, '">';
        echo '<div class="panel-heading">';
        echo '<h4 class="panel-title">', $title, '</h4>';
        echo '</div>';
		if ($body !== null) {
			echo '<div class="panel-body">';
			if (is_string($body)) {
				echo $body;
			} else {
				$body();
			}
			echo '</div>';
		}
		if ($other !== null) {
			if (is_string($other)) {
				echo $other;
			} else {
				$other();
			}
		}
        echo '</div>';
    }
}
