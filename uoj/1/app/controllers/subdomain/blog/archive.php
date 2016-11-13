<?php
	requirePHPLib('form');
	
	$blogs_cond = "poster = '".UOJContext::userid()."' and is_draft = false";
	if (!UOJContext::hasBlogPermission()) {
		$blogs_cond .= " and is_hidden = false";
	}
	
	$display_blogs_cond = $blogs_cond;
	
	if (isset($_GET['tag'])) {
		$blog_tag_required = $_GET['tag'];
		$display_blogs_cond .= " and '".DB::escape($blog_tag_required)."' in (select tag from blogs_tags where blogs_tags.blog_id = blogs.id)";
	} else {
		$blog_tag_required = null;
	}
	
	$blogs_pag = new Paginator(array(
		'col_names' => array('*'),
		'table_name' => 'blogs',
		'cond' => $display_blogs_cond,
		'tail' => 'order by post_time desc',
		'page_len' => 10
	));
	
	$all_tags = DB::selectAll("select distinct tag from blogs_tags where blog_id in (select id from blogs where $blogs_cond)");
	
	requireLib('mathjax');
	requireLib('shjs');
?>
<?php echoUOJPageHeader('日志') ?>

<div class="row">
	<div class="col-md-3">
		<?php if (UOJContext::hasBlogPermission()): ?>
		<div class="btn-group btn-group-justified">
			<a href="<?=HTML::blog_url(UOJContext::userid(), '/blog/new/write')?>" class="btn btn-primary"><span class="glyphicon glyphicon-edit"></span> 写新博客</a>
			<a href="<?=HTML::blog_url(UOJContext::userid(), '/slide/new/write')?>" class="btn btn-primary"><span class="glyphicon glyphicon-edit"></span> 写新幻灯片</a>
		</div>
		<?php endif ?>
		<div class="panel panel-info top-buffer-sm">
			<div class="panel-heading">标签</div>
			<div class="panel-body">
			<?php if ($all_tags): ?>
			<?php foreach ($all_tags as $tag): ?>
				<?php echoBlogTag($tag['tag']) ?>
			<?php endforeach ?>
			<?php else: ?>
				<div class="text-muted">暂无</div>
			<?php endif ?>
			</div>
		</div>
	</div>
	<div class="col-md-9">
		<?php if (!$blog_tag_required): ?>
			<?php if ($blogs_pag->isEmpty()): ?>
			<div class="text-muted">此人很懒，什么博客也没留下。</div>
			<?php else: ?>
			<?php foreach ($blogs_pag->get() as $blog): ?>
				<?php echoBlog($blog, array('is_preview' => true)) ?>
			<?php endforeach ?>
			<div class="text-right text-muted">共 <?= $blogs_pag->n_rows ?> 篇博客</div>
			<?php endif ?>
		<?php else: ?>
			<?php if ($blogs_pag->isEmpty()): ?>
			<div class="alert alert-danger">
				没有找到包含 “<?= HTML::escape($blog_tag_required) ?>” 标签的博客：
			</div>
			<?php else: ?>
			<div class="alert alert-success">
				共找到 <?= $blogs_pag->n_rows ?> 篇包含 “<?= HTML::escape($blog_tag_required) ?>” 标签的博客：
			</div>
			<?php foreach ($blogs_pag->get() as $blog): ?>
				<?php echoBlog($blog, array('is_preview' => true)) ?>
			<?php endforeach ?>
			<?php endif ?>
		<?php endif ?>
		
		<?= $blogs_pag->pagination() ?>
	</div>
</div>
<?php echoUOJPageFooter() ?>
