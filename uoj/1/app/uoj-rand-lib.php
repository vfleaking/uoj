<?php

function uojRand($l, $r) {
	return mt_rand($l, $r);
}

function uojRandString($len, $charset = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
	$n_chars = strlen($charset);
	$str = '';
	for ($i = 0; $i < $len; $i++) {
		$str .= $charset[uojRand(0, $n_chars - 1)];
	}
	return $str;
}

function uojRandAvaiableFileName($dir) {
	do {
		$fileName = $dir . uojRandString(20);
	} while (file_exists(UOJContext::storagePath().$fileName));
	return $fileName;
}

function uojRandAvaiableTmpFileName() {
	return uojRandAvaiableFileName('/tmp/');
}

function uojRandAvaiableSubmissionFileName() {
	$num = uojRand(1, 10000);
	if (!file_exists(UOJContext::storagePath()."/submission/$num")) {
		system("mkdir ".UOJContext::storagePath()."/submission/$num");
	}
	return uojRandAvaiableFileName("/submission/$num/");
}
