<?php

class FS {
    public static function scandir(string $directory, $cfg = []) {
        $cfg += [
            'exclude_dots' => true
        ];
        $entries = scandir($directory);
        if ($entries === false) {
            return false;
        }
        if ($cfg['exclude_dots']) {
            $entries = array_values(array_filter($entries, fn($name) => $name !== '.' && $name !== '..'));
        }
        return $entries;
    }

    public static function scandir_r(string $directory, $cfg = []) {
        foreach (FS::scandir($directory, $cfg) as $name) {
            $cur = "{$directory}/{$name}";
            if (is_dir($cur)) {
                foreach (FS::scandir_r($cur, $cfg) as $sub) {
                    yield "{$name}/{$sub}";
                }
            } else {
                yield $name;
            }
        }
    }

	/**
     * @param int $type lock type. can be either LOCK_SH or LOCK_EX
	*/
    public static function lock_file(string $path, int $type, callable $func) {
		$lock_fp = fopen($path, 'c');
		
		if (!flock($lock_fp, $type | LOCK_NB)) {
            UOJLog::error("lock failed: {$path}");
			return false;
		}
		
		$ret = $func();
		
		flock($lock_fp, LOCK_UN | LOCK_NB);

        return $ret;
    }

    public static function randomAvailableFileName($dir) {
        do {
            $name = $dir . uojRandString(20);
        } while (file_exists(UOJContext::storagePath().$name));
        return $name;
    }

    public static function randomAvailableTmpFileName() {
        return static::randomAvailableFileName('/tmp/');
    }

    public static function randomAvailableSubmissionFileName() {
        $num = uojRand(1, 10000);
        if (!file_exists(UOJContext::storagePath()."/submission/$num")) {
            system("mkdir ".UOJContext::storagePath()."/submission/$num");
        }
        return static::randomAvailableFileName("/submission/$num/");
    }

    public static function moveFilesInDir(string $src, string $dest) {
        foreach (FS::scandir($src) as $name) {
            if (!rename("{$src}/{$name}", "{$dest}/{$name}")) {
                return false;
            }
        }
        return true;
    }
}