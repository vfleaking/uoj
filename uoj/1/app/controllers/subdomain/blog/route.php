<?php

call_user_func(function() { // to prevent variable scope leak

	Route::pattern('blog_username', '[a-zA-Z0-9_\-]{1,20}');

	if (UOJConfig::$data['switch']['blog-use-subdomain']) {
		$domain = '{blog_username}.'.UOJConfig::$data['web']['blog']['host'];
		$prefix = '';
	} else {
		$domain = UOJConfig::$data['web']['blog']['host'];
		$prefix = '/blogof/{blog_username}';
	}

	Route::group([
			'domain' => $domain,
			'onload' => function() {
				UOJContext::setupBlog();
			}
		], function() use ($prefix) {
			Route::any("$prefix/", '/subdomain/blog/index.php');
			Route::any("$prefix/archive", '/subdomain/blog/archive.php');
			Route::any("$prefix/aboutme", '/subdomain/blog/aboutme.php');
			Route::any("$prefix/click-zan", '/click_zan.php');
			Route::any("$prefix/blog/{id}", '/subdomain/blog/blog.php');
			Route::any("$prefix/slide/{id}", '/subdomain/blog/slide.php');
			Route::any("$prefix/blog/(?:{id}|new)/write", '/subdomain/blog/blog_write.php');
			Route::any("$prefix/slide/(?:{id}|new)/write", '/subdomain/blog/slide_write.php');
			Route::any("$prefix/blog/{id}/delete", '/subdomain/blog/blog_delete.php');
		}
	);

});
