<?php

$tabs_info = [];

$tabs_info['statement'] = [
    'name' => '编辑',
    'url' => UOJProblem::cur()->getUri().'/manage/statement'
];

if (UOJOss::available()) {
    $tabs_info['oss'] = [
        'name' => 'OSS',
        'url' => UOJProblem::cur()->getUri().'/manage/oss'
    ];
}

$tabs_info['managers'] = [
    'name' => '管理者',
    'url' => UOJProblem::cur()->getUri().'/manage/managers'
];

$tabs_info['data'] = [
    'name' => '数据',
    'url' => UOJProblem::cur()->getUri().'/manage/data'
];

$tabs_info['return'] = [
    'name' => '返回',
    'url' => UOJProblem::cur()->getUri()
];

?>
<h1 class="page-header text-center"><?= UOJProblem::cur()->getTitle() ?> 管理</h1>
<?= HTML::tablist($tabs_info, $cur_tab) ?>
<div class="top-buffer-md"></div>