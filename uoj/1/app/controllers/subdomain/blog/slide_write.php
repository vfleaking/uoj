<?php
	requirePHPLib('form');
	
	if (!UOJContext::hasBlogPermission()) {
		become403Page();
	}
	if (isset($_GET['id'])) {
		if (!validateUInt($_GET['id']) || !($blog = queryBlog($_GET['id'])) || !UOJContext::isHisSlide($blog)) {
			become404Page();
		}
	} else {
		$blog = DB::selectFirst("select * from blogs where poster = '".UOJContext::user()['username']."' and type = 'S' and is_draft = true");
	}
	
	$blog_editor = new UOJBlogEditor();
	$blog_editor->type = 'slide';
	$blog_editor->name = 'blog';
	if ($blog) {
		$blog_editor->cur_data = array(
			'title' => $blog['title'],
			'content_md' => $blog['content_md'],
			'content' => $blog['content'],
			'tags' => queryBlogTags($blog['id']),
			'is_hidden' => $blog['is_hidden']
		);
	} else {
		$blog_editor->cur_data = array(
			'title' => '新幻灯片',
			'content_md' => '',
			'content' => '',
			'tags' => array(),
			'is_hidden' => true
		);
	}
	if ($blog && !$blog['is_draft']) {
		$blog_editor->blog_url = "/blog/{$blog['id']}";
	} else {
		$blog_editor->blog_url = null;
	}
	
	function updateBlog($id, $data) {
		DB::update("update blogs set title = '".DB::escape($data['title'])."', content = '".DB::escape($data['content'])."', content_md = '".DB::escape($data['content_md'])."', is_hidden = {$data['is_hidden']} where id = {$id}");
	}
	function insertSlide($data) {
		DB::insert("insert into blogs (type, title, content, content_md, poster, is_hidden, is_draft, post_time) values ('S', '".DB::escape($data['title'])."', '".DB::escape($data['content'])."', '".DB::escape($data['content_md'])."', '".Auth::id()."', {$data['is_hidden']}, {$data['is_draft']}, now())");
	}
	
	$blog_editor->save = function($data) {
		global $blog;
		$ret = array();
		if ($blog) {
			if ($blog['is_draft']) {
				if ($data['is_hidden']) {
					updateBlog($blog['id'], $data);
				} else {
					deleteBlog($blog['id']);
					insertSlide(array_merge($data, array('is_draft' => 0)));
					$blog = array('id' => DB::insert_id(), 'tags' => array());
					$ret['blog_write_url'] = "/slide/{$blog['id']}/write";
					$ret['blog_url'] = "/blog/{$blog['id']}";
				}
			} else {
				updateBlog($blog['id'], $data);
			}
		} else {
			$blog = array('id' => DB::insert_id(), 'tags' => array());
			if ($data['is_hidden']) {
				insertSlide(array_merge($data, array('is_draft' => 1)));
			} else {
				insertSlide(array_merge($data, array('is_draft' => 0)));
			}
		}
		if ($data['tags'] !== $blog['tags']) {
			DB::delete("delete from blogs_tags where blog_id = {$blog['id']}");
			foreach ($data['tags'] as $tag) {
				DB::insert("insert into blogs_tags (blog_id, tag) values ({$blog['id']}, '".DB::escape($tag)."')");
			}
		}
		return $ret;
	};
	
	$blog_editor->runAtServer();
?>
<?php echoUOJPageHeader('写幻灯片') ?>
<div class="text-right">
<a href="http://uoj.ac/blog/75">这玩意儿怎么用？</a>
</div>
<?php $blog_editor->printHTML() ?>
<?php echoUOJPageFooter() ?>
