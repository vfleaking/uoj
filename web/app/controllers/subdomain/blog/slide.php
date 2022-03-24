<?php
	requirePHPLib('form');
	
	UOJBlog::init(UOJRequest::get('id')) || UOJResponse::page404();
	UOJBlog::cur()->belongsToUserBlog() || UOJResponse::page404();
	UOJBlog::cur()->userCanView(Auth::user()) || UOJResponse::page403();
	!UOJBlog::cur()->isDraft() || UOJResponse::page404();
	UOJBlog::cur()->isTypeS() || UOJResponse::page404();

	$page_config = UOJContext::pageConfig();
	$page_config += [
		'PageTitle' => HTML::stripTags(UOJBlog::info('title')) . ' - 幻灯片',
		'content' => UOJBlog::cur()->queryContent()['content']
	];
	uojIncludeView('slide', $page_config);
?>
