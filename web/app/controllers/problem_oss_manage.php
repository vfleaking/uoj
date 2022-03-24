<?php

UOJOss::available() || UOJResponse::page404();
UOJOss::init();

UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJProblem::cur()->userCanManage(Auth::user()) || UOJResponse::page403();

$manager = new UOJOssObjectManager('problem-oss-manager', 'problem', UOJOss::problemPrefix(UOJProblem::cur()));

$manager->runAtServer();

?>

<?php echoUOJPageHeader(HTML::stripTags(UOJProblem::info('title')) . ' - OSS 文件管理 - 题目管理') ?>
<?php uojIncludeView('problem-manage-header', ['cur_tab' => 'oss']) ?>
<?php $manager->printHTML() ?>
<?php echoUOJPageFooter() ?>