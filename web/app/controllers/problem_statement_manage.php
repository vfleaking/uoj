<?php
	UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
	UOJProblem::cur()->userCanManage(Auth::user()) || UOJResponse::page403();

	$problem_content = UOJProblem::cur()->queryContent();
	$problem_tags = UOJProblem::cur()->queryTags();
	
	$problem_editor = new UOJBlogEditor();
	$problem_editor->name = 'problem';

	if (UOJProblem::cur()->getPresentationMode() == 'quiz') {
		$problem_editor->type = 'quiz';
	}

	$problem_editor->blog_url = UOJProblem::cur()->getUri();
	$problem_editor->cur_data = [
		'title' => UOJProblem::info('title'),
		'content_md' => $problem_content['statement_md'],
		'content' => $problem_content['statement'],
		'tags' => $problem_tags,
		'is_hidden' => UOJProblem::info('is_hidden')
	];
	$problem_editor->label_text = array_merge($problem_editor->label_text, [
		'view blog' => '查看题目',
		'blog visibility' => '题目可见性'
	]);
	
	$problem_editor->save = function($data) {
		global $problem_tags;

		$problem = UOJProblem::cur()->info;

		$change_visibility = $data['is_hidden'] != $problem['is_hidden'];

		if ($change_visibility && !$data['is_hidden']) {
			$contest_problem_pairs = DB::selectAll([
				"select * from contests_problems",
				"where", ["problem_id" => $problem['id']]
			]);

			foreach ($contest_problem_pairs as $pair) {
				$contest = UOJContest::query($pair['contest_id']);
				if ($contest->progress() < CONTEST_FINISHED) {
					return ['extra' => '当前题目出现在了一场尚未结束的比赛中，不能公开！'];
				}
			}
		}

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
		if ($change_visibility) {
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
<?php echoUOJPageHeader(HTML::stripTags(UOJProblem::info('title')) . ' - 编辑 - 题目管理') ?>
<?php uojIncludeView('problem-manage-header', ['cur_tab' => 'statement']) ?>
<?php $problem_editor->printHTML() ?>
<?php echoUOJPageFooter() ?>
