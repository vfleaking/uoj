<?php

class UOJLocalRun {
    public static string $judger_main_path, $judger_include_path, $judger_run_path;

    public static function escapearg($arg) {
        if (is_string($arg) || is_numeric($arg)) {
            return escapeshellarg($arg);
        } elseif (is_array($arg) && count($arg) == 2) {
            if ($arg[0] === '<' || $arg[0] === '>') {
                return $arg[0].escapeshellarg($arg[1]);
            } else {
                return escapeshellarg("--{$arg[0]}={$arg[1]}");
            }
        } else {
            return false;
        }
    }

    public static function getCmd($cmd) {
        if (is_array($cmd)) {
            return implode(' ', array_map('UOJLocalRun::escapearg', $cmd));
        } elseif (is_string($cmd)) {
            return $cmd;
        } else {
            return false;
        }
    }

    public static function exec($cmd, array $runp_options=null) {
        $cmd = static::getCmd($cmd);
        if ($cmd === false) {
            return false;
        }
        
        if ($runp_options) {
            $res_path = tempnam('/tmp', 'runp');
            if ($res_path === false) {
                UOJLog::error("UOJLocalRun::exec: failed to create res file. cmd: {$cmd}");
                return false;
            }
            $cmd = implode(' ', [
                escapeshellarg(static::$judger_main_path.'/run/run_program'), static::getCmd([['res', $res_path]]), static::getCmd($runp_options), $cmd
            ]);
        }

        $ret = [];
		if (exec($cmd, $ret['output'], $status) === false || $status != 0) {
            UOJLog::error("exec failed with status {$status}. cmd: {$cmd}");
            foreach ($ret['output'] as $line) {
                UOJLog::error("exec failed. output: {$line}");
            }
            return false;
        }

        if ($runp_options) {
            $fp = fopen($res_path, "r");
            if ($fp === false) {
                UOJLog::error("UOJLocalRun::exec: failed to open res file ({$res_path}). cmd: {$cmd}");
                return false;
            }
            if (fscanf($fp, '%d %d %d %d', $ret['rstype'], $ret['used_time'], $ret['used_memory'], $ret['exit_code']) != 4) {
                $ret['rstype'] = 7;
            }
            fclose($fp);
            unlink($res_path);
        }

        return $ret;
    }

    public static function execAnd(array $cmds, array $runp_options=null) {
        return static::exec(implode(' && ', array_map('UOJLocalRun::getCmd', $cmds)), $runp_options);
    }

    public static function formatter(string $src, string $dest, array $runp_options=null) {
        return static::exec([
            static::$judger_main_path.'/run/formatter', ['<', $src], ['>', $dest]
        ], $runp_options);
    }

    public static function compile(string $name, array $options, array $runp_options=null) {
        return static::exec([
            static::$judger_main_path.'/run/compile', ...$options, $name
        ], $runp_options);
    }
}

UOJLocalRun::$judger_main_path = realpath('/home/local_main_judger/judge_client/uoj_judger');
UOJLocalRun::$judger_include_path = UOJLocalRun::$judger_main_path.'/include';
UOJLocalRun::$judger_run_path = UOJLocalRun::$judger_main_path.'/run';