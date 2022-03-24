<?php

requirePHPLib('form');
requirePHPLib('judger');
requirePHPLib('svn');

UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJProblem::cur()->userCanManage(Auth::user()) || UOJResponse::page403();

$problem = UOJProblem::info();
$problem_configure = new UOJProblemConfigure(UOJProblem::cur());

$problem_configure->runAtServer();

?>
<?php echoUOJPageHeader(HTML::stripTags($problem['title']) . ' - 数据配置 - 题目管理') ?>
<h1 class="page-header text-center">#<?=$problem['id']?> : <?=$problem['title']?> 数据配置</h1>

<?php $problem_configure->printHTML() ?>

<?php echoUOJPageFooter() ?>