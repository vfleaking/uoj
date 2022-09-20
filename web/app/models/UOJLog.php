<?php

class UOJLog {
	public static function error() {
        $msg = '';
        foreach (func_get_args() as $var) {
            if ($msg !== '') {
                $msg .= ', ';
            }
            if (!is_string($var)) {
                $msg .= var_export($var, true);
            } else {
                $msg .= $var;
            }
        }
        error_log('[uoj error] '.$msg);
	}
	
	public static function warning() {
        $msg = '';
        foreach (func_get_args() as $var) {
            if ($msg !== '') {
                $msg .= ', ';
            }
            if (!is_string($var)) {
                $msg .= var_export($var, true);
            } else {
                $msg .= $var;
            }
        }
        error_log('[uoj warning] '.$msg);
	}

    public static function error_meta_val_not_set($name) {
        UOJLog::error("$name is not set in the meta table");
    }
}
