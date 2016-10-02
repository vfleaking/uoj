<?php
	$blogs = DB::selectAll("select blogs.id, title, poster, post_time from important_blogs, blogs where is_hidden = 0 and important_blogs.blog_id = blogs.id order by level desc, important_blogs.blog_id desc limit 5");
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
					<?php foreach ($blogs as $blog): ?>
						<?php
							$now_cnt++;
							$new_tag = '';
							if ((time() - strtotime($blog['post_time'])) / 3600 / 24 <= 7) {
								$new_tag = '<sup style="color:red">&nbsp;new</sup>';
							}
						?>
						<tr>
							<td><a href="/blog/<?= $blog['id'] ?>"><?= $blog['title'] ?></a><?= $new_tag ?></td>
							<td>by <?= getUserLink($blog['poster']) ?></td>
							<td><small><?= $blog['post_time'] ?></small></td>
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
	<div class="col-sm-12">
		<h3><?= UOJLocale::get('top rated') ?></h3>
		<?php echoRanklist(array('echo_full' => '', 'top10' => '')) ?>
		<div class="text-center">
			<a href="/ranklist"><?= UOJLocale::get('view all') ?></a>
		</div>
	</div>
</div>
<?php echoUOJPageFooter() ?>
