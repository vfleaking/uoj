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
        # 'Java7'     => 'Java 7',
        'Java8'     => 'Java 8',
        'Java11'     => 'Java 11',
        # 'Java14'     => 'Java 14',
        'Java17'     => 'Java 17',
        'Pascal'    => 'Pascal',
    ];

    public static $lang_upgrade_map = [
        'Java7' => 'Java8',
        'Java14' => 'Java17'
    ];

    public static $default_preferred_language = 'C++14';

    /**
     * a map from suffix to language code
     * be sure to make it the same as the one in uoj_judger/include/uoj_run.h on judgers
     */
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
        '17.java' => 'Java17',
    ];

    public static function getUpgradedLangCode($lang) {
        return isset(static::$lang_upgrade_map[$lang]) ? static::$lang_upgrade_map[$lang] : $lang;
    }

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
            ['Java8', 'Java11', 'Java17']
        ];
        foreach ($list as $lang) {
            $lang = static::getUpgradedLangCode($lang);
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
        $lang = static::getUpgradedLangCode($lang);
        if (isset(static::$supported_languages[$lang])) {
            return static::$supported_languages[$lang];
        } elseif ($lang === '/') {
            return $lang;
        } else {
            return '?';
        }
    }

    public static function getRunTypeFromLanguage(string $lang): string {
        $lang = static::getUpgradedLangCode($lang);
        switch ($lang) {
            case "Python2.7":
                return "python2.7";
		    case "Python3":
                return "python3";
		    case "Java8":
                return "java8";
		    case "Java11":
                return "java11";
		    case "Java17":
                return "java17";
            default:
                return "default";
        }
    }

    public static function getLanguagesCSSClass(string $lang): string {
        $lang = static::getUpgradedLangCode($lang);
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
            case 'Java8':
            case 'Java11':
            case 'Java17':
                return 'sh_java';
            case 'C':
                return 'sh_c';
            case 'Pascal':
                return 'sh_pascal';
            default:
                return '';
        }
    }

    /**
     * return a list of currently supported languages that match with a given language query string
     * if a language is outdated, be sure to change the "language" field in various submissions tables!
     */
    public static function getMatchedLanguages(string $lang): array {
        $lang = strtolower(preg_replace('/\s+/', '', $lang));
        if ($lang == 'c++') {
            return ['C++', 'C++11', 'C++14', 'C++17', 'C++20'];
        } elseif ($lang == 'python2') {
            return ['Python2.7'];
        } elseif ($lang == 'python') {
            return ['Python2.7', 'Python3'];
        } elseif ($lang == 'java') {
            return ['Java8', 'Java11', 'Java17'];
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
                return ['path' => "{$name}{$suf}", 'lang' => static::getUpgradedLangCode($lang)];
            }
        }
        return false;
    }
}