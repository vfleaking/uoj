<?php
	UOJContest::init(UOJRequest::get('id')) || UOJResponse::page404();
    UOJContest::cur()->userCanRegister(Auth::user(), ['ensure' => true]);

	$contest = UOJContest::info();
	
	$register_form = new UOJForm('register');
	$register_form->handle = function() {
        UOJContest::cur()->userRegister(Auth::user());
	};
	$register_form->submit_button_config['class_str'] = 'btn btn-primary';
	$register_form->submit_button_config['text'] = '报名比赛';
	$register_form->succ_href = "/contests";
	
	$register_form->runAtServer();
?>
<?php echoUOJPageHeader(HTML::stripTags($contest['name']) . ' - 报名') ?>
<div class="row">
    <div class="col-lg-offset-1 col-lg-10">
		<h1 class="page-header">比赛规则：<?=UOJLocale::get('contests::'.$contest['extra_config']['basic_rule'])?></h1>
		<ul>
			<li>比赛报名后不算正式参赛，报名后进了比赛页面也不算参赛，<strong>看了题目才算正式参赛</strong>。如果未正式参赛则不算 rating。</li>
			<?php if ($contest['extra_config']['basic_rule'] === 'UOJ-OI'): ?>
			<li>同一道题可以多次提交，没有 Compile Error 的提交为有效提交。若同一题有多次有效提交按<strong>最后一次有效提交</strong>算成绩。</li>
			<li>比赛中途提交后，可以看到<strong>测样例</strong>的结果。（若为提交答案题则对于每个测试点，该测试点有分则该测试点为满分）</li>
			<li>比赛结束后会进行最终测试，最终测试后的排名为最终排名。</li>
			<li>比赛排名按分数为第一关键字，完成时间为第二关键字。完成时间等于每道题最后一次有效提交的提交时间之和（无视掉爆零的题目）。</li>
			<?php elseif ($contest['extra_config']['basic_rule'] === 'UOJ-IOI'): ?>
			<li>同一道题可以多次提交，没有 Compile Error 的提交为有效提交。若同一题有多次有效提交按<strong>得分最高的有效提交</strong>算成绩。</li>
			<li>比赛中途提交后，可以看到<strong>最终得分</strong>。</li>
			<li>比赛排名按分数为第一关键字，完成时间为第二关键字。完成时间等于每道题获得最高分的有效提交的提交时间之和（无视掉爆零的题目）。</li>
			<?php endif ?>
			<li>请遵守比赛规则，一位选手在一场比赛内不得报名多个账号，选手之间不能交流或者抄袭代码，如果被检测到将以0分处理或者封禁。</li>
		</ul>
		<?php $register_form->printHTML(); ?>
	</div>
</div>
<?php echoUOJPageFooter() ?>
