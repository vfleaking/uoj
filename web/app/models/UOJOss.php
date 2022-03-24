<?php

use OSS\Core\OssException;
use OSS\OssClient;

class UOJOss {
    public static OssClient $client;

    public static function available() {
        return isset(UOJConfig::$data['oss']) && empty(UOJConfig::$data['oss']['disabled']);
    }

    public static function accessKeyId() {
        return UOJConfig::$data['oss']['accessKeyId'];
    }

    public static function accessKeySecret() {
        return UOJConfig::$data['oss']['accessKeySecret'];
    }

    public static function region() {
        return UOJConfig::$data['oss']['region'];
    }

    public static function endpoint() {
        return UOJOss::region().(empty(UOJConfig::$data['oss']['internal']) ? '' : '-internal').'.aliyuncs.com';
    }

    public static function init() {
        if (!UOJOss::available()) {
            UOJResponse::page404();
        }
        try {
            UOJOss::$client = new OssClient(UOJOss::accessKeyId(), UOJOss::accessKeySecret(), UOJOss::endpoint());
        } catch (OssException $e) {
            UOJLog::error($e->getMessage());
            UOJResponse::page503('OSS 连接失败，请稍后重试');
        }
    }

    public static function bucket($choice = 'main', $key = null) {
        $res = UOJConfig::$data['oss']['bucket'];
        if ($key === null) {
            return empty($res['name']) ? $res[$choice] : $res;
        } else {
            return empty($res['name']) ? $res[$choice][$key] : $res[$key];
        }
    }

    public static function bucketName($choice = 'main') {
        return UOJOss::bucket($choice, 'name');
    }

    public static function bucketWebLocation($choice = 'main') {
        return UOJOss::bucket($choice, 'web-location');
    }

    public static function problemPrefix(UOJProblem $problem) {
        return "problem/{$problem->info['id']}/";
    }
}