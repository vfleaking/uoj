<?php
	requirePHPLib('form');
	
	function echoBlogCell($blog) {
		echo '<tr>';
		echo '<td>' . getBlogLink($blog['id']) . '</td>';
		echo '<td>' . getUserLink($blog['poster']) . '</td>';
		echo '<td>' . $blog['post_time'] . '</td>';
		echo '</tr>';
	}
	$header = <<<EOD
	<tr>
		<th width="60%">标题</th>
		<th width="20%">发表者</th>
		<th width="20%">发表日期</th>
	</tr>
EOD;
	$config = array();
	$config['table_classes'] = array('table', 'table-hover');
?>
<?php echoUOJPageHeader(UOJLocale::get('blogs')) ?>
<?php if (Auth::check()): ?>
<div class="pull-right">
	<a href="<?= HTML::blog_url(Auth::id(), '/') ?>" class="btn btn-default btn-sm">我的博客首页</a>
</div>
<?php endif ?>
<h3>博客总览</h3>
<?php echoLongTable(array('id', 'poster', 'title', 'post_time', 'zan'), 'blogs', 'is_hidden = 0', 'order by post_time desc', $header, 'echoBlogCell', $config); ?>
<?php echoUOJPageFooter() ?>
