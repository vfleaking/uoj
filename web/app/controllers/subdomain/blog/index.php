<?php
	$blogs_pag = new Paginator([
		'col_names' => ['*'],
		'table_name' => 'blogs',
		'cond' => [
			"poster" => UOJUserBlog::id(),
            "is_hidden" => 0
        ],
		'tail' => 'order by post_time desc limit 5',
		'echo_full' => true
    ]);
?>
<?php
	$REQUIRE_LIB['mathjax'] = '';
	$REQUIRE_LIB['shjs'] = '';
?>
<?php echoUOJPageHeader(UOJUserBlog::id() . '的博客') ?>

<div class="row">
	<div class="col-md-9">
		<?php if ($blogs_pag->isEmpty()): ?>
		<div class="text-muted">此人很懒，什么博客也没留下。</div>
		<?php else: ?>
		<?php
			foreach ($blogs_pag->get() as $blog_info) {
				$blog = new UOJBlog($blog_info);
				$blog->echoView(['is_preview' => true]);
			}
		?>
		<?php endif ?>
	</div>
	<div class="col-md-3">
		<img class="media-object img-thumbnail center-block" alt="<?= UOJUserBlog::id() ?> Avatar" src="<?= HTML::avatar_addr(UOJUserBlog::user(), 256) ?>" />
	</div>
</div>
<?php echoUOJPageFooter() ?>
