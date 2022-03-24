
<ul class="nav nav-tabs" role="tablist">
	<li class="active"><a href="#tab-question" role="tab" data-toggle="tab">提问</a></li>
	<?php if ($post_notice): ?>
		<li><a href="#tab-notice" role="tab" data-toggle="tab">公告</a></li>
	<?php endif ?>
	<?php if ($standings_data): ?>
		<li><a href="#tab-standings" role="tab" data-toggle="tab">终榜</a></li>
	<?php endif ?>
	<li><a href="#tab-anti-cheat" role="tab" data-toggle="tab">反作弊</a></li>
</ul>
<div class="tab-content">
	<div class="tab-pane active" id="tab-question">
		<h3>提问</h3>
		<?php uojIncludeView('contest-question-table', ['pag' => $questions_pag, 'can_reply' => true, 'reply_question' => $reply_question]) ?>
	</div>
	<?php if ($post_notice): ?>
		<div class="tab-pane" id="tab-notice">
			<h3>发布比赛公告</h3>
			<?php $post_notice->printHTML() ?>
		</div>
	<?php endif ?>
	<?php if ($standings_data): ?>
		<div class="tab-pane" id="tab-standings">
			<h3>终榜</h3>
			<?php uojIncludeView('contest-standings', $standings_data) ?>
		</div>
	<?php endif ?>
	<div class="tab-pane" id="tab-anti-cheat">
		<h3>参赛选手IP访问记录</h3>
		<div>
			参赛选手IP访问记录表下载：<a href="/download.php?type=contestip&id=<?= $contest['id'] ?>">下载</a>
		</div>
		<div><strong>注意：</strong>UOJ仅存储一定时间的访问记录，请在比赛结束后立即下载，否则信息可能不准确。</div>
	</div>
</div>
