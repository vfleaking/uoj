<?php

class UOJSubmissionArchive {

    public ZipArchive $zip_file;
    public int $total_size = 0;
    public array $content = [];

    public function __construct() {
        $this->zip_file = new ZipArchive();
    }

    public static function create($zip_file_name_gen) {
        $archive = new UOJSubmissionArchive();

        $zip_file_name = $zip_file_name_gen();

        if ($archive->zip_file->open(UOJContext::storagePath().$zip_file_name, ZipArchive::CREATE) !== true) {
            throw new UOJSubmissionFailedException("可能是服务器空间不足？请重试或联系管理员！");
        }

        $archive->content = [];
        $archive->content['file_name'] = $zip_file_name;
        $archive->content['config'] = [];

        return $archive;
    }

    public function unlink() {
        unlink(UOJContext::storagePath().$this->content['file_name']);
    }

    public function close() {
        $this->zip_file->close();
    }

    public function addFromString(array $req, string $str) {
        $this->zip_file->addFromString($req['file_name'], $str);
        $this->postAdd($req);
    }

    public function addFromFile(array $req, string $filepath) {
        $this->zip_file->addFile($filepath, $req['file_name']);
        $this->postAdd($req);
    }

    private function postAdd(array $req) {
        $stat = $this->zip_file->statName($req['file_name']);
        
        if ($req['type'] == 'source code') {
            $max_size = isset($req['size']) ? (int)$req['size'] : 50;
            if ($stat['size'] > $max_size * 1024) {
                throw new UOJSubmissionFailedException("源代码长度不能超过 {$max_size}KB。");
            }
        }
        
        $this->total_size += $stat['size'];
    }
}
