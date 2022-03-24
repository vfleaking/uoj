<?php

class UOJResponse {
    public static function message($msg, $title = '消息') {
        if (UOJContext::isAjax()) {
            die($msg);
        } else {
            echoUOJPageHeader($title);
            echo $msg;
            echoUOJPageFooter();
            die();
        }
    }
    public static function page403($msg = null) {
        header($_SERVER['SERVER_PROTOCOL'] . " 403 Forbidden", true, 403); 
        if ($msg === null) {
            $msg = '<div class="text-center"><div style="font-size:233px">403</div><p>禁止入内！ T_T</p></div>';
        }
        UOJResponse::message($msg, '403');
    }
    public static function page404($msg = null) {
        header($_SERVER['SERVER_PROTOCOL'] . " 404 Not Found", true, 404);
        if ($msg === null) {
            $msg = '<div class="text-center"><div style="font-size:233px">404</div><p>唔……未找到该页面……你是从哪里点进来的……&gt;_&lt;……</p></div>';
        }
        self::message($msg, '404');
    }
    public static function page406($msg = null) {
        header($_SERVER['SERVER_PROTOCOL'] . " 406 Not Acceptable", true, 406);
        if ($msg === null) {
            $msg = '<div class="text-center"><div style="font-size:233px">406</div><p>服务器对你提交的请求好像不太满意</p></div>';
        }
        self::message($msg, '406');
    }
    public static function page500($msg) {
        header($_SERVER['SERVER_PROTOCOL'] . " 500 Internal Server Error", true, 500);
        self::message($msg, '500');
    }
    public static function page503($msg) {
        header($_SERVER['SERVER_PROTOCOL'] . " 503 Service Unavailable", true, 503);
        self::message($msg, '503');
    }

    public static function xsendfile($path, $cfg = []) {
        $cfg += [
            'mimetype' => null,
            'attachment' => null
        ];

        $path = realpath($path);
        if (!is_file($path)) {
            self::page404();
        }

        if ($cfg['mimetype'] === null) {
            $cfg['mimetype'] = (new finfo(FILEINFO_MIME))->file($path);
            if ($cfg['mimetype'] === false) {
                self::page404();
            }
        }

        header("X-Sendfile: $path");
        header("Content-type: {$cfg['mimetype']}");
        if ($cfg['attachment'] !== null) {
            header("Content-Disposition: attachment; filename={$cfg['attachment']}");
        }
        die();
    }

    public static function echofile($echo_func, $cfg = []) {
        $cfg += [
            'mimetype' => 'text/plain',
            'attachment' => null
        ];

        header("Content-type: {$cfg['mimetype']}");
        if ($cfg['attachment'] !== null) {
            header("Content-Disposition: attachment; filename={$cfg['attachment']}");
        }
        $echo_func();
        die();
    }
}