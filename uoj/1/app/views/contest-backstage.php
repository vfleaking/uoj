
<ul class="nav nav-tabs" role="tablist">
	<li class="active"><a href="#tab-question" role="tab" data-toggle="tab">提问</a></li>
	<?php if ($post_notice): ?>
		<li><a href="#tab-notice" role="tab" data-toggle="tab">公告</a></li>
	<?php endif ?>
	<?php if ($standings_data): ?>
		<li><a href="#tab-standings" role="tab" data-toggle="tab">终榜</a></li>
	<?php endif ?>
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
</div>
