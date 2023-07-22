<?php
	UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
	UOJProblem::cur()->userCanManage(Auth::user()) || UOJResponse::page403();

	$problem = UOJProblem::cur()->info;
	$problem_content = UOJProblem::cur()->queryContent();
	$problem_tags = UOJProblem::cur()->queryTags();
	
	$problem_editor = new UOJBlogEditor();
	$problem_editor->name = 'problem';

	if (UOJProblem::cur()->getPresentationMode() == 'quiz') {
		$problem_editor->type = 'quiz';
	}

	$problem_editor->blog_url = UOJProblem::cur()->getUri();
	$problem_editor->cur_data = [
		'title' => $problem['title'],
		'content_md' => $problem_content['statement_md'],
		'content' => $problem_content['statement'],
		'tags' => $problem_tags,
		'is_hidden' => $problem['is_hidden']
	];
	$problem_editor->label_text = array_merge($problem_editor->label_text, [
		'view blog' => '查看题目',
		'blog visibility' => '题目可见性'
	]);
	
	$problem_editor->save = function($data) {
		global $problem, $problem_tags;
		DB::update([
            "update problems",
            "set", ["title" => $data['title']],
            "where", ["id" => $problem['id']]
        ]);
		DB::update([
            "update problems_contents",
            "set", [
                "statement" => $data['content'],
                "statement_md" => $data['content_md']
            ], "where", ["id" => $problem['id']]
        ]);
		
		if ($data['tags'] !== $problem_tags) {
			DB::delete([
                "delete from problems_tags",
                "where", ["problem_id" => $problem['id']]
            ]);
			foreach ($data['tags'] as $tag) {
				DB::insert([
                    "insert into problems_tags",
                    "(problem_id, tag)",
                    "values", DB::tuple([$problem['id'], $tag])
                ]);
			}
		}
		if ($data['is_hidden'] != $problem['is_hidden'] ) {
			DB::update([
                "update problems",
                "set", ["is_hidden" => $data['is_hidden']],
                "where", ["id" => $problem['id']]
            ]);
			DB::update([
                "update submissions",
                "set", ["is_hidden" => $data['is_hidden']],
                "where", ["problem_id" => $problem['id']]
            ]);
			DB::update([
                "update hacks",
                "set", ["is_hidden" => $data['is_hidden']],
                "where", ["problem_id" => $problem['id']]
            ]);
		}
	};
	
	$problem_editor->runAtServer();
?>
<?php echoUOJPageHeader(HTML::stripTags($problem['title']) . ' - 编辑 - 题目管理') ?>
<?php uojIncludeView('problem-manage-header', ['cur_tab' => 'statement']) ?>
<?php $problem_editor->printHTML() ?>
<?php echoUOJPageFooter() ?>
