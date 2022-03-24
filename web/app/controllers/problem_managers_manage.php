<?php
	requirePHPLib('form');
	requirePHPLib('svn');

	UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
	UOJProblem::cur()->userCanManage(Auth::user()) || UOJResponse::page403();

	$managers_form = newAddDelCmdForm('managers',
		'validateUserAndStoreByUsername',
		function($type, $username, &$vdata) {
			$user = $vdata['user'][$username];
			if ($type == '+') {
				DB::insert([
                    "insert into problems_permissions",
                    "(problem_id, username)",
                    "values", DB::tuple([UOJProblem::info('id'), $user['username']])
                ]);
			} else if ($type == '-') {
				DB::delete([
                    "delete from problems_permissions",
                    "where", [
                        "problem_id" => UOJProblem::info('id'),
                        "username" => $user['username']
                    ]
                ]);
			}
		},
		function() {
			svnRefreshPasswordOfProblem(UOJProblem::info('id'));
		}
	);
	
	$managers_form->runAtServer();
?>
<?php echoUOJPageHeader(HTML::stripTags(UOJProblem::info('title')) . ' - 管理者 - 题目管理') ?>
<?php uojIncludeView('problem-manage-header', ['cur_tab' => 'managers']) ?>

<table class="table table-hover">
	<thead>
		<tr>
			<th>#</th>
			<th>用户名</th>
		</tr>
	</thead>
	<tbody>
	<?php
		$row_id = 0;
		$res = DB::selectAll([
			"select username from problems_permissions",
			"where", ["problem_id" => UOJProblem::info('id')]
		]);
		foreach ($res as $row) {
			$row_id++;
			echo '<tr>', '<td>', $row_id, '</td>', '<td>', getUserLink($row['username']), '</td>', '</tr>';
		}
	?>
	</tbody>
</table>
<p class="text-center">命令格式：命令一行一个，+mike表示把mike加入管理者，-mike表示把mike从管理者中移除</p>
<?php $managers_form->printHTML(); ?>
<?php echoUOJPageFooter() ?>
