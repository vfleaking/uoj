<?php

Route::pattern('blog_username', '[a-zA-Z0-9_\-]{1,20}');

Route::group([
		'domain' => '{blog_username}.'.UOJConfig::$data['web']['blog']['host'],
		'onload' => function() {
			UOJContext::setupBlog();
		}
	], function() {
		Route::any('/', '/subdomain/blog/index.php');
		Route::any('/archive', '/subdomain/blog/archive.php');
		Route::any('/aboutme', '/subdomain/blog/aboutme.php');
		Route::any('/click-zan', '/click_zan.php');
		Route::any('/blog/{id}', '/subdomain/blog/blog.php');
		Route::any('/slide/{id}', '/subdomain/blog/slide.php');
		Route::any('/blog/(?:{id}|new)/write', '/subdomain/blog/blog_write.php');
		Route::any('/slide/(?:{id}|new)/write', '/subdomain/blog/slide_write.php');
		Route::any('/blog/{id}/delete', '/subdomain/blog/blog_delete.php');
	}
);
