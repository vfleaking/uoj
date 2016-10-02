<?php
	requirePHPLib('form');
	if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
		become404Page();
	}
	genMoreContestInfo($contest);
	
	if ($myUser == null) {
		redirectToLogin();
	} elseif (hasContestPermission($myUser, $contest) || hasRegistered($myUser, $contest) || $contest['cur_progress'] != CONTEST_NOT_STARTED) {
		redirectTo('/contests');
	}
	
	$register_form = new UOJForm('register');
	$register_form->handle = function() {
		global $myUser, $contest;
		mysql_query("insert into contests_registrants (username, user_rating, contest_id, has_participated) values ('{$myUser['username']}', {$myUser['rating']}, {$contest['id']}, 0)");
		updateContestPlayerNum($contest);
	};
	$register_form->submit_button_config['class_str'] = 'btn btn-primary';
	$register_form->submit_button_config['text'] = '报名比赛';
	$register_form->succ_href = "/contests";
	
	$register_form->runAtServer();
?>
<?php echoUOJPageHeader(HTML::stripTags($contest['name']) . ' - 报名') ?>
<h1 class="page-header">比赛规则</h1>
<ul>
	<li>比赛报名后不算正式参赛，报名后进了比赛页面也不算参赛，<strong>看了题目才算正式参赛</strong>。如果未正式参赛则不算rating。</li>
	<li>比赛中途可以提交，若同一题有多次提交按<strong>最后一次不是Compile Error的提交</strong>算成绩。（其实UOJ会自动无视你所有Compile Error的提交当作没看见）</li>
	<li>比赛中途提交后，可以看到<strong>测样例</strong>的结果。（若为提交答案题则对于每个测试点，该测试点有分则该测试点为满分）</li>
	<li>比赛结束后会进行最终测试，最终测试后的排名为最终排名。</li>
	<li>比赛排名按分数为第一关键字，完成题目的总时间为第二关键字。完成题目的总时间等于完成每道题所花时间之和（无视掉爆零的题目）。</li>
	<li>请遵守比赛规则，一位选手在一场比赛内不得报名多个账号，选手之间不能交流或者抄袭代码，如果被检测到将以0分处理或者封禁。</li>
</ul>
<?php $register_form->printHTML(); ?>
<?php echoUOJPageFooter() ?>
