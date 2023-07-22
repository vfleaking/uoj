<?php
	function echoBlogCell(UOJBlog $blog) {
		echo '<tr>';
		echo '<td>' . $blog->getLink(['show_level' => true, 'show_new_tag' => true]) . '</td>';
		echo '<td>' . getUserLink($blog->info['poster']) . '</td>';
		echo '<td>' . $blog->info['post_time'] . '</td>';
		echo '</tr>';
	}
	$header = <<<EOD
	<tr>
		<th width="60%">标题</th>
		<th width="20%">发表者</th>
		<th width="20%">发表日期</th>
	</tr>
	EOD;
	$config = [
		'table_classes' => ['table', 'table-hover'],
		'page_len' => 100
	];
?>
<?php echoUOJPageHeader(UOJLocale::get('announcements')) ?>
<h3>公告</h3>
<?php
    echoLongTable(
        ['blogs.id', 'poster', 'title', 'post_time', 'zan', 'level', 'is_new'], 'important_blogs, blogs', [
            'is_hidden' => 0,
            'important_blogs.blog_id' => DB::raw('blogs.id')
        ],
        'order by level desc, important_blogs.blog_id desc',
        $header, function($info) {
			echoBlogCell(new UOJBlog($info));
		}, $config
    );
?>
<?php echoUOJPageFooter() ?>
