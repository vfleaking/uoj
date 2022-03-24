<?php
	requirePHPLib('form');
	
	UOJUserBlog::userCanManage(Auth::user()) || UOJResponse::page403();

	$blog_type = UOJRequest::option(UOJRequest::GET, 'type', ['B', 'S']);
	$blog_type || UOJResponse::page404();

	if (isset($_GET['id'])) {
		UOJBlog::init(UOJRequest::get('id')) || UOJResponse::page404();
		UOJBlog::cur()->belongsToUserBlog() || UOJResponse::page404();
		!UOJBlog::cur()->isDraft() || UOJResponse::page404();
		UOJBlog::info('type') == $blog_type || UOJResponse::page404();
		$blog = UOJBlog::info();
		$blog['content'] = UOJBlog::cur()->queryContent()['content'];
		$blog['content_md'] = UOJBlog::cur()->queryContent()['content_md'];
	} else {
		$blog = DB::selectFirst([
            "select * from blogs",
            "where", [
                "poster" => UOJUserBlog::id(), 
                "type" => $blog_type,
                "is_draft" => true
            ]
        ]);
		if ($blog) {
			(new UOJBlog($blog))->setAsCur();
		}
	}
	
	$blog_editor = new UOJBlogEditor();
	if ($blog_type === 'S') {
		$blog_editor->type = 'slide';
	}
	$blog_editor->name = 'blog';
	if ($blog) {
		$blog_editor->cur_data = [
			'title' => $blog['title'],
			'content_md' => $blog['content_md'],
			'content' => $blog['content'],
			'tags' => UOJBlog::cur()->queryTags(),
			'is_hidden' => $blog['is_hidden']
		];
	} else {
		$blog_editor->cur_data = [
			'title' => $blog_type == 'B' ? '新博客' : '新幻灯片',
			'content_md' => '',
			'content' => '',
			'tags' => [],
			'is_hidden' => true
		];
	}
	if ($blog && !$blog['is_draft']) {
		$blog_editor->blog_url = "/blog/{$blog['id']}";
	} else {
		$blog_editor->blog_url = null;
	}
	
	function updateBlog($id, $data) {
		DB::update([
            "update blogs",
            "set", [
                "title" => $data['title'],
                "content" => $data['content'],
                "content_md" => $data['content_md'],
                "is_hidden" => $data['is_hidden']
            ], "where", ["id" => $id]
        ]);
	}
	function insertBlog($data) {
		global $blog_type;
		DB::insert([
            "insert into blogs",
            "(type, title, content, content_md, poster, is_hidden, is_draft, post_time, active_time)",
            "values", DB::tuple([
				$blog_type, $data['title'], $data['content'], $data['content_md'],
				Auth::id(), $data['is_hidden'], $data['is_draft'], DB::now(), DB::now()
            ])
        ]);
	}
	
	$blog_editor->save = function($data) {
		global $blog, $blog_type;
		$ret = [];
		if ($blog) {
			if ($blog['is_draft']) {
				if ($data['is_hidden']) {
					updateBlog($blog['id'], $data);
				} else {
					UOJBlog::cur()->delete();
					insertBlog(array_merge($data, ['is_draft' => 0]));
					(new UOJBlog(['id' => DB::insert_id(), 'type' => $blog_type]))->setAsCur();
					UOJBlog::cur()->tags = [];
					$ret['blog_write_url'] = UOJBlog::cur()->getUriForWrite();
					$ret['blog_url'] = UOJBlog::cur()->getBlogUri();
				}
			} else {
				updateBlog($blog['id'], $data);
			}
		} else {
			if ($data['is_hidden']) {
				insertBlog(array_merge($data, ['is_draft' => 1]));
			} else {
				insertBlog(array_merge($data, ['is_draft' => 0]));
			}
			(new UOJBlog(['id' => DB::insert_id(), 'type' => $blog_type]))->setAsCur();
			UOJBlog::cur()->tags = [];
			if (!$data['is_hidden']) {
				$ret['blog_write_url'] = UOJBlog::cur()->getUriForWrite();
				$ret['blog_url'] = UOJBlog::cur()->getBlogUri();
			}
		}
		UOJBlog::cur()->updateTags($data['tags']);
		return $ret;
	};
	
	$blog_editor->runAtServer();
?>
<?php if ($blog_type == 'B'): ?>
<?php echoUOJPageHeader('写博客') ?>
<div class="text-right">
<a href="https://uoj.ac/blog/7">这玩意儿怎么用？</a>
</div>
<?php $blog_editor->printHTML() ?>
<?php echoUOJPageFooter() ?>
<?php elseif ($blog_type == 'S'): ?>
<?php echoUOJPageHeader('写幻灯片') ?>
<div class="text-right">
<a href="https://uoj.ac/blog/75">这玩意儿怎么用？</a>
</div>
<?php $blog_editor->printHTML() ?>
<?php echoUOJPageFooter() ?>
<?php endif ?>