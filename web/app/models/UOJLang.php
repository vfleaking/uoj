<?php

class UOJLang {
    public static $supported_languages = [
        'C++'       => 'C++ 03',
        'C++11'     => 'C++ 11',
        'C++14'     => 'C++ 14',
        'C++17'     => 'C++ 17',
        'C++20'     => 'C++ 20',
        'C'         => 'C',
        'Python3'   => 'Python 3',
        'Python2.7' => 'Python 2.7',
        'Java7'     => 'Java 7',
        'Java8'     => 'Java 8',
        'Java11'     => 'Java 11',
        'Java14'     => 'Java 14',
        'Pascal'    => 'Pascal',
    ];

    public static $default_preferred_language = 'C++11';

    public static $suffix_map = [
        '.code'  => null,
        '20.cpp' => 'C++20',
        '17.cpp' => 'C++17',
        '14.cpp' => 'C++14',
        '11.cpp' => 'C++11',
        '.cpp'   => 'C++',
        '.c'     => 'C',
        '.pas'   => 'Pascal',
        '2.7.py' => 'Python2.7',
        '.py'    => 'Python3',
        '7.java' => 'Java7',
        '8.java' => 'Java8',
        '11.java' => 'Java11',
        '14.java' => 'Java14',
    ];

    public static function getAvailableLanguages($list = null): array {
        if ($list === null) {
            return static::$supported_languages;
        }
        if (!is_array($list)) {
            return [];
        }
        $is_avail = [];
        $dep_list = [
            ['C++', 'C++11', 'C++14', 'C++17', 'C++20'],
            ['Java7', 'Java8', 'Java11', 'Java14']
        ];
        foreach ($list as $lang) {
            foreach ($dep_list as $dep) {
                $ok = false;
                foreach ($dep as $d) {
                    if ($ok || $d == $lang) {
                        $is_avail[$d] = true;
                        $ok = true;
                    }
                }
            }
            $is_avail[$lang] = true;
        }

        $langs = [];
        foreach (static::$supported_languages as $lang_code => $lang_display) {
            if (isset($is_avail[$lang_code])) {
                $langs[$lang_code] = $lang_display;
            }
        }
        return $langs;
    }

    public static function getLanguageDisplayName(string $lang): string {
        if (isset(static::$supported_languages[$lang])) {
            return static::$supported_languages[$lang];
        } elseif ($lang === '/') {
            return $lang;
        } else {
            return '?';
        }
    }

    public static function getRunTypeFromLanguage(string $lang): string {
        switch ($lang) {
            case "Python2.7":
                return "python2.7";
		    case "Python3":
                return "python3";
		    case "Java7":
                return "java7";
		    case "Java8":
                return "java8";
		    case "Java11":
                return "java11";
		    case "Java14":
                return "java14";
            default:
                return "default";
        }
    }

    public static function getLanguagesCSSClass(string $lang): string {
        switch ($lang) {
            case 'C++':
            case 'C++11':
            case 'C++14':
            case 'C++17':
            case 'C++20':
                return 'sh_cpp';
            case 'Python2.7':
            case 'Python3':
                return 'sh_python';
            case 'Java7':
            case 'Java8':
            case 'Java11':
            case 'Java14':
                return 'sh_java';
            case 'C':
                return 'sh_c';
            case 'Pascal':
                return 'sh_pascal';
            default:
                return '';
        }
    }

    public static function getMatchedLanguages(string $lang): array {
        $lang = strtolower(preg_replace('/\s+/', '', $lang));
        if ($lang == 'c++') {
            return ['C++', 'C++11', 'C++14', 'C++17', 'C++20'];
        } elseif ($lang == 'python2') {
            return ['Python2.7'];
        } elseif ($lang == 'python') {
            return ['Python2.7', 'Python3'];
        } elseif ($lang == 'java') {
            return ['Java7', 'Java8', 'Java11', 'Java14'];
        } else {
            foreach (static::$supported_languages as $lang_code => $lang_display) {
                if ($lang === strtolower(preg_replace('/\s+/', '', $lang_display))) {
                    return [$lang_code];
                }
            }
            return [$lang];
        }
    }

    /**
     * @return array|false
     */
    public static function findSourceCode(string $name, string $root='', $is_file='is_file') {
        if ($root !== '' && !strEndWith($root, '/')) {
            $root .= '/';
        } 
        foreach (static::$suffix_map as $suf => $lang) {
            if ($is_file("{$root}{$name}{$suf}")) {
                return ['path' => "{$name}{$suf}", 'lang' => $lang];
            }
        }
        return false;
    }
}