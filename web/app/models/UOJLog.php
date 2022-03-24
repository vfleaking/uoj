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
}
