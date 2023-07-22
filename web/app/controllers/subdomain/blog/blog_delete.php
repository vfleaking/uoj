<?php
	UOJBlog::init(UOJRequest::get('id')) || UOJResponse::page404();
	UOJBlog::cur()->belongsToUserBlog() || UOJResponse::page404();
	UOJBlog::cur()->userCanManage(Auth::user()) || UOJResponse::page403();
	!UOJBlog::cur()->isDraft() || UOJResponse::page404();
	
	$delete_form = new UOJForm('delete');
	$delete_form->handle = function() {
		UOJBlog::cur()->delete();
	};
	$delete_form->submit_button_config['class_str'] = 'btn btn-danger';
	$delete_form->submit_button_config['text'] = '是的，我确定要删除';
	$delete_form->succ_href = "/archive";
	
	$delete_form->runAtServer();
?>
<?php echoUOJPageHeader('删除博客 - ' . HTML::stripTags(UOJBlog::info('title'))) ?>
<h3>您真的要删除博客 <?= UOJBlog::info('title') ?> 吗？该操作不可逆！</h3>
<?php $delete_form->printHTML(); ?>
<?php echoUOJPageFooter() ?>
