<?php

class UOJBlog {
    use UOJDataTrait;
	use UOJArticleTrait;

    public static function query($id) {
        if (!isset($id) || !validateUInt($id)) {
            return null;
        }
        $info = DB::selectFirst([
			"select id, title, post_time, active_time, poster, zan, is_hidden, type, is_draft from blogs",
		    "where", ['id' => $id]
        ]);
        if (!$info) {
            return null;
        }
        return new UOJBlog($info);
    }

    public function __construct($info) {
        $this->info = $info;
    }
	
	/**
	 * Check if the blog belongs to the current user blog
	 */
	public function belongsToUserBlog() {
		return UOJContext::type() == 'blog' && UOJUserBlog::id() === $this->info['poster'];
	}

    public function userCanView(array $user = null) {
		return !$this->info['is_hidden'] || $this->userCanManage($user);
    }

	public function userCanManage(array $user = null) {
		return UOJUserBlog::userCanManage($user, $this->info['poster']);
	}

	public function isDraft() {
		return $this->info['is_draft'];
	}

	public function isTypeB() {
		return $this->info['type'] === 'B';
	}
	public function isTypeS() {
		return $this->info['type'] === 'S';
	}

	public function getTitle(array $cfg = []) {
		$title = $this->info['title'];
		return $title;
	}

	public function getBlogUri($where = '') {
		return "/blog/{$this->info['id']}{$where}";
	}

	public function getSlideUri($where = '') {
		return "/slide/{$this->info['id']}{$where}";
	}

	public function getUriForWrite() {
		return $this->isTypeB() ? $this->getBlogUri('/write') : $this->getSlideUri('/write');
	}

	public function getLink(array $cfg = []) {
		// title has been escaped by the blog editor
		$link = '';
		if (!empty($cfg['show_level'])) {
			$level_str = $this->getLevelString();
			if ($level_str !== '') {
				$link .= "{$level_str} ";
			}
		}
		$link .= '<a href="'.HTML::blog_url($this->info['poster'], $this->getBlogUri()).'">'.$this->getTitle($cfg).'</a>';
		if (!empty($cfg['show_new_tag'])) {
			if ($this->isNew()) {
				$link .= '<sup style="color:red">&nbsp;new</sup>';
			}
		}
		return $link;
	}

	/**
	 * Return true if the blog is marked as "new".
	 * Call this function only if info['is_new'] has been filled by the corresponding value in the table important_blogs.
	 */
	public function isNew() {
		return (time() - strtotime($this->info['post_time'])) / 3600 / 24 <= 7 || $this->info['is_new'];
	}

	/**
	 * Return the string that shows the importance level of this blog.
	 * Call this function only if info['level'] has been filled by the corresponding value in the table important_blogs.
	 */
	public function getLevelString() {
		$level = $this->info['level'];
		switch ($level) {
			case 1:
				return '<span style="color:red">[三级置顶]</span>';
			case 2:
				return '<span style="color:red">[二级置顶]</span>';
			case 3:
				return '<span style="color:red">[一级置顶]</span>';
			default: // such as 0
				return '';
		}
	}
	
	public function queryNewestComment() {
		return DB::selectFirst([
			"select * from blogs_comments",
			"where", [
				'blog_id' => $this->info['id'],
				'is_hidden' => false
			],
			"order by post_time desc",
			DB::limit(1)
		]);
	}
	
	public function updateActiveTime() {
		$active_time = $this->info['post_time'];
		
		$newest = $this->queryNewestComment();
		if ($newest) {
			$active_time = $newest['post_time'];
		}
		
		DB::update([
			"update blogs",
			"set", ['active_time' => $active_time],
			"where", ['id' => $this->info['id']]
		]);
	}

	public static function deleteByID($id) {
		$id = (int)$id;
		DB::delete([
			"delete from click_zans",
			"where", [
				'type' => 'B',
				'target_id' => $id
			]
		]);
		DB::delete([
			"delete from click_zans",
			"where", [
				'type' => 'BC',
				[
					"target_id", "in", DB::rawbracket([
						"select id from blogs_comments",
						"where", ["blog_id" => $id]
					])
				]
			]
		]);
		DB::delete([
			"delete from blogs",
			"where", ['id' => $id]
		]);
		DB::delete([
			"delete from blogs_comments",
			"where", ['blog_id' => $id]
		]);
		DB::delete([
			"delete from important_blogs",
			"where", ['blog_id' => $id]
		]);
		DB::delete([
			"delete from blogs_tags",
			"where", ['blog_id' => $id]
		]);
	}

	public function delete() {
		self::deleteByID($this->info['id']);
	}

	public function echoView(array $cfg = []) {
		// load tags and content into cache
		$this->queryContent();
		$this->queryTags();

		$cfg += [
			'blog' => $this,
			'show_title_only' => false,
			'is_preview' => false
		];
		uojIncludeView('blog-preview', $cfg);
	}
}

UOJBlog::$table_for_content = 'blogs';
UOJBlog::$key_for_content = 'id';
UOJBlog::$fields_for_content = ['content', 'content_md'];
UOJBlog::$table_for_tags = 'blogs_tags';
UOJBlog::$key_for_tags = 'blog_id';