<?php
	$blogs_pag = new Paginator(array(
		'col_names' => array('*'),
		'table_name' => 'blogs',
		'cond' => "poster = '".UOJContext::user()['username']."' and is_hidden = 0",
		'tail' => 'order by post_time desc limit 5',
		'echo_full' => true
	));
?>
<?php
	$REQUIRE_LIB['mathjax'] = '';
	$REQUIRE_LIB['shjs'] = '';
?>
<?php echoUOJPageHeader(UOJContext::user()['username'] . '的博客') ?>

<div class="row">
	<div class="col-md-9">
		<?php if ($blogs_pag->isEmpty()): ?>
		<div class="text-muted">此人很懒，什么博客也没留下。</div>
		<?php else: ?>
		<?php foreach ($blogs_pag->get() as $blog): ?>
			<?php echoBlog($blog, array('is_preview' => true)) ?>
		<?php endforeach ?>
		<?php endif ?>
	</div>
	<div class="col-md-3">
		<img class="media-object img-thumbnail center-block" alt="<?= UOJContext::user()['username'] ?> Avatar" src="<?= HTML::avatar_addr(UOJContext::user(), 256) ?>" />
	</div>
</div>
<?php echoUOJPageFooter() ?>
