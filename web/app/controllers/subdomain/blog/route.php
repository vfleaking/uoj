<?php

Route::pattern('blog_username', '[a-zA-Z0-9_\-]{1,20}');

Route::group([
		'domain' => '{blog_username}.'.UOJConfig::$data['web']['blog']['host'],
		'protocol' => UOJConfig::$data['web']['blog']['protocol'],
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
		Route::any('/blog/(?:{id}|new)/write', '/subdomain/blog/blog_write.php?type=B');
		Route::any('/slide/(?:{id}|new)/write', '/subdomain/blog/blog_write.php?type=S');
		Route::any('/blog/{id}/delete', '/subdomain/blog/blog_delete.php');
		Route::any('/blog/{id}/content.md', '/download.php?type=blog-md');
		Route::any('/slide/{id}/content.yaml', '/download.php?type=slide-yaml');
	}
);
