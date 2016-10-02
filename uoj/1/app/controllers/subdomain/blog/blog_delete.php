<?php
	requirePHPLib('form');
	
	if (!UOJContext::hasBlogPermission()) {
		become403Page();
	}
	if (!isset($_GET['id']) || !validateUInt($_GET['id']) || !($blog = queryBlog($_GET['id'])) || !UOJContext::isHis($blog)) {
		become404Page();
	}
	
	$delete_form = new UOJForm('delete');
	$delete_form->handle = function() {
		global $blog;
		deleteBlog($blog['id']);
	};
	$delete_form->submit_button_config['class_str'] = 'btn btn-danger';
	$delete_form->submit_button_config['text'] = '是的，我确定要删除';
	$delete_form->succ_href = "/archive";
	
	$delete_form->runAtServer();
?>
<?php echoUOJPageHeader('删除博客 - ' . HTML::stripTags($blog['title'])) ?>
<h3>您真的要删除博客 <?= $blog['title'] ?> 吗？该操作不可逆！</h3>
<?php $delete_form->printHTML(); ?>
<?php echoUOJPageFooter() ?>
