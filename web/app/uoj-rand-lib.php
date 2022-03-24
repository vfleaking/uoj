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

function uojRandSvnPassword() {
	return uojRandString(10);
}