<?php

class Auth {
	public static function check() {
		global $myUser;
		return $myUser !== null;
	}
	public static function id() {
		global $myUser;
		if ($myUser === null) {
			return null;
		}
		return $myUser['username'];
	}
	public static function user() {
		global $myUser;
		return $myUser;
    }
    public static function property($name) {
        global $myUser;
        if (!$myUser) {
            return false;
        }
        return $myUser[$name];
    }
	public static function login($username, $remember = true) {
		if (!validateUsername($username)) {
			return;
		}
		$_SESSION['username'] = $username;
		if ($remember) {
			$remember_token = DB::selectSingle([
                "select remember_token from user_info",
                "where", ["username" => $username]
            ]);
			if ($remember_token == '') {
				$remember_token = uojRandString(60);
				DB::update([
                    "update user_info",
                    "set", ["remember_token" => $remember_token],
                    "where", ["username" => $username]
                ]);
			}

			$expire = time() + 60 * 60 * 24 * 365 * 10;
			Cookie::safeSet('uoj_username', $username, $expire, '/', array('httponly' => true));
			Cookie::safeSet('uoj_remember_token', $remember_token, $expire, '/', array('httponly' => true));
		}
	}
	public static function logout() {
		unset($_SESSION['username']);
		unset($_SESSION['last_visited']);
		Cookie::safeUnset('uoj_username', '/');
		Cookie::safeUnset('uoj_remember_token', '/');
		DB::update([
            "update user_info",
            "set", ["remember_token" => ''],
            "where", ["username" => Auth::id()]
        ]);
	}

	private static function initMyUser() {
		global $myUser;
		$myUser = null;
		
		Cookie::safeCheck('uoj_username', '/');
		Cookie::safeCheck('uoj_remember_token', '/');
		
		if (isset($_SESSION['username'])) {
			if (!validateUsername($_SESSION['username'])) {
				return;
			}
			$myUser = UOJUser::query($_SESSION['username']);
			return;
		}

		$remember_token = Cookie::safeGet('uoj_remember_token', '/');
		if ($remember_token != null) {
			$username = Cookie::safeGet('uoj_username', '/');
			if (!validateUsername($username)) {
				return;
			}
			$myUser = UOJUser::query($username);
			if (!$myUser) return;
			if ($myUser['remember_token'] !== $remember_token) {
				$myUser = null;
				return;
			}
			$_SESSION['username'] = $myUser['username'];
			return;
		}
	}
	public static function init() {
		global $myUser;
		
		Auth::initMyUser();
		if ($myUser && UOJUser::getAccountStatus($myUser) != 'ok') {
			$myUser = null;
		}
		if ($myUser) {
			$myUser = UOJUser::updateVisitHistory($myUser, [
				'remote_addr' => UOJContext::remoteAddr(),
                'http_x_forwarded_for' => UOJContext::httpXForwardedFor(),
                'http_user_agent' => UOJContext::httpUserAgent()
            ]);
			$_SESSION['last_visited'] = time();
		}
	}
}
