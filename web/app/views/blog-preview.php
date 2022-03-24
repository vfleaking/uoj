<?php
	if ($is_preview) {
		$readmore_pos = strpos($blog->content['content'], '<!-- readmore -->');
		if ($readmore_pos !== false) {
			$content = substr($blog->content['content'], 0, $readmore_pos).'<p><a href="/blog/'.$blog->info['id'].'">阅读更多……</a></p>';
		} else {
			$content = $blog->content['content'];
		}
	} else {
		$content = $blog->content['content'];
	}
	
	$extra_text = $blog->info['is_hidden'] ? '<span class="text-muted">[已隐藏]</span> ' : '';
	
	$blog_type = $blog->info['type'] == 'B' ? 'blog' : 'slide';
?>
<h2><?= $extra_text ?><a class="header-a" href="/blog/<?= $blog->info['id'] ?>"><?= $blog->info['title'] ?></a></h2>
<div><?= $blog->info['post_time'] ?> <strong>By</strong> <?= getUserLink($blog->info['poster']) ?></div>
<?php if (!$show_title_only): ?>
<div class="panel panel-default">
	<div class="panel-body">
		<?php if ($blog->isTypeB()): ?>
		<article class="uoj-article"><?= $content ?></article>
		<?php elseif ($blog->isTypeS()): ?>
		<article class="uoj-article">
			<div class="embed-responsive embed-responsive-16by9">
				<iframe class="embed-responsive-item" src="/slide/<?= $blog->info['id'] ?>"></iframe>
			</div>
			<div class="text-right top-buffer-sm">
				<a class="btn btn-default btn-md" href="/slide/<?= $blog->info['id'] ?>"><span class="glyphicon glyphicon-fullscreen"></span> 全屏</a>
			</div>
		</article>
		<?php endif ?>
	</div>
	<div class="panel-footer text-right">
		<ul class="list-inline bot-buffer-no">
			<li>
			<?php foreach ($blog->tags as $tag): ?>
				<?php echoBlogTag($tag) ?>
			<?php endforeach ?>
			</li>
			<?php if ($is_preview): ?>
  			<li><a href="/blog/<?= $blog->info['id'] ?>">阅读全文</a></li>
  			<?php endif ?>
  			<?php if (Auth::check() && (isSuperUser(Auth::user()) || Auth::id() == $blog->info['poster'])): ?>
			<li><a href="/<?=$blog_type?>/<?=$blog->info['id']?>/write">修改</a></li>
			<li><a href="/blog/<?=$blog->info['id']?>/delete">删除</a></li>
			<?php endif ?>
  			<li><?= ClickZans::getBlock('B', $blog->info['id'], $blog->info['zan']) ?></li>
		</ul>
	</div>
</div>
<?php endif ?>
