<?php

class UOJBlogComment {
    use UOJDataTrait;

    public static function query($id) {
        if (!isset($id) || !validateUInt($id)) {
            return null;
        }
        $info = DB::selectFirst([
			"select * from blogs_comments",
		    "where", ['id' => $id]
        ]);
        if (!$info) {
            return null;
        }
        return new UOJBlogComment($info);
    }

    public function __construct($info) {
        $this->info = $info;
    }

    public function hide($reason) {
        DB::update([
            "update blogs_comments",
            "set", [
                'is_hidden' => ($reason !== ''),
                'reason_to_hide' => $reason
            ],
            "where", ['id' => $this->info['id']]
        ]);

        $blog = UOJBlog::query($this->info['blog_id']);
        if ($blog) {
            $blog->updateActiveTime($blog);
        }
    }
}