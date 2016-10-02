<?php
	requirePHPLib('form');
	requirePHPLib('svn');
	
	if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
		become404Page();
	}
	if (!hasProblemPermission($myUser, $problem)) {
		become403Page();
	}
	
	$managers_form = newAddDelCmdForm('managers',
		function($username) {
			if (!validateUsername($username) || !queryUser($username)) {
				return "不存在名为{$username}的用户";
			}
			return '';
		},
		function($type, $username) {
			global $problem;
			if ($type == '+') {
				mysql_query("insert into problems_permissions (problem_id, username) values (${problem['id']}, '$username')");
			} else if ($type == '-') {
				mysql_query("delete from problems_permissions where problem_id = ${problem['id']} and username = '$username'");
			}
		},
		function() {
			global $problem;
			svnRefreshPasswordOfProblem($problem['id']);
		}
	);
	
	$managers_form->runAtServer();
?>
<?php echoUOJPageHeader(HTML::stripTags($problem['title']) . ' - 管理者 - 题目管理') ?>
<h1 class="page-header" align="center">#<?=$problem['id']?> : <?=$problem['title']?> 管理</h1>
<ul class="nav nav-tabs" role="tablist">
	<li><a href="/problem/<?= $problem['id'] ?>/manage/statement" role="tab">编辑</a></li>
	<li class="active"><a href="/problem/<?= $problem['id'] ?>/manage/managers" role="tab">管理者</a></li>
	<li><a href="/problem/<?= $problem['id'] ?>/manage/data" role="tab">数据</a></li>
	<li><a href="/problem/<?=$problem['id']?>" role="tab">返回</a></li>
</ul>

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
	$result = mysql_query("select username from problems_permissions where problem_id = ${problem['id']}");
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$row_id++;
		echo '<tr>', '<td>', $row_id, '</td>', '<td>', getUserLink($row['username']), '</td>', '</tr>';
	}
?>
	</tbody>
</table>
<p class="text-center">命令格式：命令一行一个，+mike表示把mike加入管理者，-mike表示把mike从管理者中移除</p>
<?php $managers_form->printHTML(); ?>
<?php echoUOJPageFooter() ?>
