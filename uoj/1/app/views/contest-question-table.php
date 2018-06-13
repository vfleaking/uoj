<?php

if (!isset($can_reply)) {
	$can_reply = false;
	$reply_question = null;
}

?>
<div class="table-responsive">
	<table class="table table-bordered table-hover table-vertical-middle table-text-center">
		<thead>
			<tr>
				<th style="width:10em">提问者</th>
				<th style="width:7em">提问时间</th>
				<th>问题</th>
			</tr>
		</thead>
		<tbody>
			<?php if ($pag->isEmpty()): ?>
				<tr><td colspan="233"><?= UOJLocale::get('none') ?></td></tr>
			<?php else: foreach ($pag->get() as $question): ?>
				<tr>
					<td><?= getUserLink($question['username']) ?></td>
					<td class="small"><?= $question['post_time'] ?></td>
					<td style="text-align: left" class="question" data-qid="<?=$question['id']?>">
						<div class="question-content uoj-readmore"><?= HTML::escape($question['question']) ?></div>
						<?php if ($can_reply): ?>
							<div class="text-right">
								<button type="button" class="btn btn-primary btn-xs question-reply-button">编辑回复</button>
							</div>
						<?php endif ?>
						<hr class="top-buffer-sm bot-buffer-md" />
						<?php if ($question['answer'] === ''): ?>
							<div class="question-content"><strong class="text-muted">暂无回复</strong></div>
						<?php else: ?>
							<div class="question-content uoj-readmore"><strong class="text-danger">回复：</strong><span class="text-warning"><?= HTML::escape($question['answer']) ?></span></div>
						<?php endif ?>
					</td>
				</tr>
			<?php endforeach; endif ?>
		</tbody>
	</table>
</div>
<?= $pag->pagination() ?>

<?php if ($reply_question): ?>
<div id="div-reply" style="display:none" class="top-buffer-md bot-buffer-md">
	<?php $reply_question->printHTML() ?>
</div>
<script type="text/javascript">
var onclick = function(p) {
	return function() {
		qid = parseInt($(p).data('qid'))
		q = '#div-reply';
		t = '#input-rid';
		r = '#input-rcontent';
	
		if ($(q).data('qid') != qid) {
			$(q).data('qid', qid);
			$(q).hide('fast', function() {
				$(this).appendTo(p).show('fast', function() {
					$(t).val(qid);
					$(r).val('').focus();
				});
			});
		} else if ($(q).css('display') != 'none') {
			$(q).appendTo(p).hide('fast');
		} else {
			$(q).appendTo(p).show('fast', function() {
				$(t).val(qid);
				$(r).val('').focus();
			});
		}
	};
};

$('.question').each(function() {
	$(this).find('.question-reply-button').click(onclick(this));
});

var onselectchange = function() {
	switch ($('#input-rtype').val()) {
		case 'private':
		case 'public':
			$('#input-rcontent').prop('readonly', false);
			break;
		default:
			$('#input-rcontent').val('').prop('readonly', true);
			break;
	}
};

$(document).ready(onselectchange);
$('#input-rtype').on('change', onselectchange);
</script>
<?php endif ?>
