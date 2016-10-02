<?php
	requirePHPLib('form');
	
	if (!isset($_GET['id']) || !validateUInt($_GET['id']) || !($blog = queryBlog($_GET['id'])) || !UOJContext::isHisSlide($blog)) {
		become404Page();
	}
	if ($blog['is_hidden'] && !UOJContext::hasBlogPermission()) {
		become403Page();
	}
	
	$page_config = UOJContext::pageConfig();
	$page_config['PageTitle'] = HTML::stripTags($blog['title']) . ' - 幻灯片';
	$page_config['content'] = $blog['content'];
	uojIncludeView('slide', $page_config);
?>
