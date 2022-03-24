<?php
	$blogs = DB::selectAll([
        "select blogs.id, title, poster, is_new, post_time from important_blogs, blogs",
        "where", [
            "is_hidden" => 0,
            "important_blogs.blog_id" => DB::raw("blogs.id")
        ], "order by level desc, important_blogs.blog_id desc",
        DB::limit(6)
    ]);
?>
<?php echoUOJPageHeader('UOJ') ?>
<div class="panel panel-default">
	<div class="panel-body">
		<div class="row">
			<div class="col-sm-12 col-md-9">
				<table class="table">
					<thead>
						<tr>
							<th style="width:60%"><?= UOJLocale::get('announcements') ?></th>
							<th style="width:20%"></th>
							<th style="width:20%"></th>
						</tr>
					</thead>
				  	<tbody>
					<?php $now_cnt = 0; ?>
					<?php foreach ($blogs as $blog_info): ?>
						<?php
							$blog = new UOJBlog($blog_info);
							$now_cnt++;
						?>
						<tr>
							<td><?= $blog->getLink(['show_new_tag' => true]) ?></td>
							<td>by <?= getUserLink($blog->info['poster']) ?></td>
							<td><small><?= $blog->info['post_time'] ?></small></td>
						</tr>
					<?php endforeach ?>
					<?php for ($i = $now_cnt + 1; $i <= 5; $i++): ?>
						<tr><td colspan="233">&nbsp;</td></tr>
					<?php endfor ?>
						<tr><td class="text-right" colspan="233"><a href="/announcements"><?= UOJLocale::get('all the announcements') ?></a></td></tr>
					</tbody>
				</table>
			</div>
			<div class="col-xs-6 col-sm-4 col-md-3">
				<img class="media-object img-thumbnail" src="/pictures/UOJ.png" alt="UOJ logo" />
			</div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-md-6">
		<h3><?= UOJLocale::get('top rated > active users') ?></h3>
		<?php UOJRanklist::printHTML(['top10' => true, 'active_users_only' => true]) ?>
		<div class="text-center">
			<a href="/ranklist"><?= UOJLocale::get('view all') ?></a>
		</div>
	</div>
	<div class="col-md-6">
		<h3><?= UOJLocale::get('top rated > all users') ?></h3>
		<?php UOJRanklist::printHTML(['top10' => true]) ?>
		<div class="text-center">
			<a href="/ranklist/all-users"><?= UOJLocale::get('view all') ?></a>
		</div>
	</div>
</div>
<?php echoUOJPageFooter() ?>
