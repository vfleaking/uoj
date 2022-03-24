<?php

trait UOJDataTrait {
    public $info;
    protected static $cur = null;

    public static function query() {
        return null;
    }

    public static function init() {
        $cur = call_user_func_array('static::query', func_get_args());
        if (!$cur) {
            return false;
        }
        static::$cur = $cur;
        return true;
    }

    /**
     * @return static
     */
    public static function cur() {
        return static::$cur;
    }
    
    public static function info($key = null) {
        if ($key === null) {
            return static::$cur->info;
        } else {
            return static::$cur->info[$key];
        }
    }

    public function setAsCur() {
        static::$cur = $this;
        return $this;
    }
}