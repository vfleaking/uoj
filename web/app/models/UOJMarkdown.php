<?php

class UOJMarkdown {

    public static $v8_uoj_marked = null;
    public static $v8_slide_marked = null;

    public static $aux_url = "http://127.0.0.1:7513";

    public static $config = null;

	public static function compile_from_markdown($md, $cfg = []) {
		$cfg += [
			'type' => 'uoj'
		];

        if (!isset(static::$config)) {
            static::$config = UOJContext::getMeta('markdown');
        }

        if (static::$config['backend'] == 'v8js') {
            return static::compile_from_markdown_v8($md, $cfg);
        } else {
            return static::compile_from_markdown_aux($md, $cfg);
        }
	}

	private static function compile_from_markdown_v8($md, $cfg) {
		if ($cfg['type'] == 'uoj') {
            if (!isset(static::$v8_uoj_marked)) {
                try {
                    static::$v8_uoj_marked = new V8Js('PHP');
                    static::$v8_uoj_marked->executeString(file_get_contents(UOJContext::documentRoot().'/public/js/uoj-marked.js'), 'marked.js');
                } catch (V8JsException $e) {
                    static::$v8_uoj_marked = null;
                    return false;
                }
            }
            $v8 = static::$v8_uoj_marked;
		} elseif ($cfg['type'] == 'slide') {
            if (!isset(static::$v8_slide_marked)) {
                try {
                    static::$v8_slide_marked = new V8Js('PHP');
                    static::$v8_slide_marked->executeString(file_get_contents(UOJContext::documentRoot().'/public/js/marked.js'), 'marked.js');
                    static::$v8_slide_marked->executeString(<<<EOD
                        marked.setOptions({
                            getLangClass: function(lang) {
                                lang = lang.toLowerCase();
                                switch (lang) {
                                    case 'c': return 'c';
                                    case 'c++': return 'cpp';
                                    case 'pascal': return 'pascal';
                                    default: return lang;
                                }
                            },
                            getElementClass: function(tok) {
                                switch (tok.type) {
                                    case 'list_item_start':
                                        return 'fragment';
                                    case 'loose_item_start':
                                        return 'fragment';
                                    default:
                                        return null;
                                }
                            }
                        })
                        EOD
                    );
                } catch (V8JsException $e) {
                    static::$v8_slide_marked = null;
                    return false;
                }
            }
            $v8 = static::$v8_slide_marked;
		} else {
			return false;
		}

        try {
            $v8->md = $md;
            return $v8->executeString('marked(PHP.md)');
        } catch (V8JsException $e) {
            return false;
        }
    }

	private static function compile_from_markdown_aux($md, $cfg) {
        if (!isset(static::$config['timeout'])) {
            UOJLog::error_meta_val_not_set('markdown.timeout');
            return false;
        }

        $option = [
            'http' => [
                'header' => [
                    'Content-Type: text/plain',
                    'Context-Length: ' . strlen($md)
                ],
                'method' => 'POST',
                'content' => $md,
                'timeout' => static::$config['timeout']
            ]
        ];
        $context = stream_context_create($option);
		if ($cfg['type'] == 'uoj') {
			$result = @file_get_contents(static::$aux_url."/render-md/uoj", false, $context);
			return $result;
		} elseif ($cfg['type'] == 'slide') {
			$result = @file_get_contents(static::$aux_url."/render-md/slide", false, $context);
			return $result;
		} else {
			return false;
		}
    }
}