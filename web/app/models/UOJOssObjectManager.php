<?php

class UOJOssObjectManager {
    public string $name;
    public string $bucket_choice;
    public string $root;
    public string $url;
    public OSS\Model\ObjectListInfo $query_res;
    public DropzoneForm $dropzone_form;

    public function __construct($name, $bucket_choice, $root) {
		requireLib('oss-sdk');
        
        $this->name = $name;

        $this->bucket_choice = $bucket_choice;
        $this->root = $root;

        $this->url = UOJContext::requestURI();

        $this->query_res = UOJOss::$client->listObjects(UOJOss::bucketName($this->bucket_choice), [
            'delimiter' => '',
            'prefix' => $this->root
        ]);

        $this->dropzone_form = new DropzoneForm($name, [
            'uploadMultiple' => false
        ], [
            'success' => 'updateObjectTable'
        ]);
        $this->dropzone_form->handler = function(DropzoneForm $form) {
            $file = $form->getFile();
            try {
                UOJOss::$client->uploadFile(UOJOss::bucketName($this->bucket_choice), "{$this->root}{$file['name']}", $file['tmp_name']);
            } catch (Exception $e) {
                UOJResponse::page406('上传出错');
            }
            die('上传成功');
        };
    }

    public function deleteObject($name) {
        !strEndWith($name, '/') || UOJResponse::page404();
        try {
            UOJOss::$client->deleteObject(UOJOss::bucketName($this->bucket_choice), "{$this->root}{$name}");
        } catch (Exception $e) {
            UOJResponse::message('删除失败！');
        }
    }

    public function objectTableID() {
        return "{$this->name}-table";
    }

    public function objectTable() {
        $objects = array_filter($this->query_res->getObjectList(), function($obj) {
            return !strEndWith($obj->getKey(), '/');
        });
        return HTML::responsive_table(['文件', '链接', '操作'], $objects, [
            'tr' => function($obj, $idx) {
                $name = substr($obj->getKey(), strlen($this->root));

                $html = [];
                $html[] = '<tr>';
                $html[] = HTML::tag('td', [], HTML::escape($name));
                $html[] = HTML::tag('td', [], HTML::tag('a', [], HTML::autolink(
                    HTML::url("/{$obj->getKey()}", ['location' => UOJOss::bucketWebLocation($this->bucket_choice)]),
                    ['target' => '_blank']
                )));
                $html[] = HTML::tag('td', [], HTML::tag(
                    'a', [
                        'class' => 'btn btn-danger btn-xs',
                        'href' => HTML::url('?', [
                            'params' => [
                                'del' => true,
                                'name' => $name,
                            ],
                            'with_token' => true,
                            'escape' => false
                        ])
                    ], '删除')
                );
                $html[] = '</tr>';
                return implode($html);
            },
            'table_attr' => [
                'id' => "{$this->name}-table"
            ]
        ]);
    }

    public function runAtServer() {
        if (UOJRequest::get('del') !== null) {
            crsf_defend();
            is_string(UOJRequest::get('name')) || UOJResponse::page404();
            $this->deleteObject(UOJRequest::get('name'));
            redirectTo(HTML::url('?'));
        }
        if (UOJRequest::get('table') !== null) {
            die($this->objectTable());
        }
        $this->dropzone_form->runAtServer();
    }

    public function printHTML() {
        try {
            return uojIncludeView('oss-object-manager', ['manager' => $this]);
        } catch (Exception $e) {
            UOJLog::error($e->getMessage());
            UOJResponse::page503('访问该题目 OSS 文件时出现错误，请稍后重试');
        }
    } 
}