<form method="post" class="form-horizontal" id="form-<?= $editor->name ?>" enctype="multipart/form-data">
<?= HTML::hiddenToken() ?>
<div class="row">
	<div class="col-sm-6">
		<?= HTML::div_vinput("{$editor->name}_title", 'text', $editor->label_text['title'], html_entity_decode($editor->cur_data['title'])) ?>
	</div>
	<div class="col-sm-6">
		<?= HTML::div_vinput("{$editor->name}_tags", 'text', $editor->label_text['tags'], join(', ', $editor->cur_data['tags'])) ?>
	</div>
</div>
<?= HTML::div_vtextarea("{$editor->name}_content_md", $editor->label_text['content'], $editor->cur_data['content_md']) ?>
<div class="row">
	<div class="col-sm-6">
		<?php if ($editor->blog_url): ?>
		<a id="a-<?= $editor->name ?>_view_blog" class="btn btn-info" href="<?= HTML::escape($editor->blog_url) ?>"><?= $editor->label_text['view blog'] ?></a>
		<?php else: ?>
		<a id="a-<?= $editor->name ?>_view_blog" class="btn btn-info" style="display: none;"><?= $editor->label_text['view blog'] ?></a>
		<?php endif ?>
	</div>
	<div class="col-sm-6 text-right">
		<?= HTML::checkbox("{$editor->name}_is_hidden", $editor->cur_data['is_hidden']) ?>
	</div>
</div>
</form>
<script type="text/javascript">
$('#<?= "input-{$editor->name}_is_hidden" ?>').bootstrapSwitch({
	onText: <?= json_encode($editor->label_text['private']) ?>,
	onColor: 'danger',
	offText: <?= json_encode($editor->label_text['public']) ?>,
	offColor: 'primary',
	labelText: <?= json_encode($editor->label_text['blog visibility']) ?>,
	handleWidth: 100
});
blog_editor_init("<?= $editor->name ?>", <?= json_encode(array('type' => $editor->type)) ?>);
</script>
