<?php

// return bool

function validateUsername($username) {
	return is_string($username) && preg_match('/^[a-zA-Z0-9_]{1,20}$/', $username);
}

function validatePassword($password) {
	return is_string($password) && preg_match('/^[a-z0-9]{32}$/', $password);
}

function validateEmail($email) {
	return is_string($email) && strlen($email) <= 50 && preg_match('/^(.+)@(.+)$/', $email);
}

function validateQQ($QQ) {
	return is_string($QQ) && strlen($QQ) <= 15 && preg_match('/^[0-9]{5,15}$/', $QQ);
}

function validateMotto($motto) {
	return is_string($motto) && ($len = mb_strlen($motto, 'UTF-8')) !== false && $len <= 50;
}

function validateUInt($x) { // [0, 1000000000)
	if (!is_string($x)) {
		return false;
	}
	if ($x === '0') {
		return true;
	}
	return preg_match('/^[1-9][0-9]{0,8}$/', $x);
}

function validateInt($x) {
	if (!is_string($x)) {
		return false;
	}
	if ($x[0] == '-') {
		$x = substr($x, 1);
	}
	return validateUInt($x);
}

function validateUploadedFile($name) {
	return isset($_FILES[$name]) && is_uploaded_file($_FILES[$name]['tmp_name']);
}

function validateIP($ip) {
	return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

function is_short_string($str) {
	return is_string($str) && strlen($str) <= 256;
}

// return str

function validateBlogId($id) {
    if (!validateUInt($id)) {
        return 'ID不合法';
    }
	$blog = UOJBlog::query($id);
    if (!$blog) {
        return '博客不存在';
    }
    return ['error' => '', 'store' => $blog];
}

function validateCommentId($id) {
    if (!validateUInt($id)) {
        return 'ID不合法';
	}
	$comment = UOJBlogComment::query($id);
	if (!$comment) {
        return '评论不存在';
	}
    return ['error' => '', 'store' => $comment];
}

function validateContestId($id) {
    if (!validateUInt($id)) {
        return 'ID不合法';
    }
	$contest = UOJContest::query($id);
	if (!$contest) {
        return '比赛不存在';
    }
    return ['error' => '', 'store' => $contest];
}

function validateUserAndStoreByUsername($username, &$vdata) {
	if (!isset($vdata['user'])) {
		$vdata['user'] = [];
	}
	$user = UOJUser::query($username);
	if (!$user) {
		return "不存在名为{$username}的用户";
	}
	$vdata['user'][$username] = $user;
	return '';
}

function validateNothing($x) {
    return '';
}

function validateString($x) {
	return is_string($x) ? '' : '不合法的字符串';
}