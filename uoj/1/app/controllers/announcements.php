<?php
	requirePHPLib('form');
	
	function echoBlogCell($blog) {
		$level = $blog['level'];
		
		switch ($level) {
			case 0:
				$level_str = '';
				break;
			case 1:
				$level_str = '<span style="color:red">[三级置顶]</span> ';
				break;
			case 2:
				$level_str = '<span style="color:red">[二级置顶]</span> ';
				break;
			case 3:
				$level_str = '<span style="color:red">[一级置顶]</span> ';
				break;
		}
		
		echo '<tr>';
		echo '<td>' . $level_str . getBlogLink($blog['id']) . '</td>';
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
	$config = [
		'table_classes' => ['table', 'table-hover'],
		'page_len' => 100
	];
?>
<?php echoUOJPageHeader(UOJLocale::get('announcements')) ?>
<h3>公告</h3>
<?php echoLongTable(array('blogs.id', 'poster', 'title', 'post_time', 'zan', 'level'), 'important_blogs, blogs', 'is_hidden = 0 and important_blogs.blog_id = blogs.id', 'order by level desc, important_blogs.blog_id desc', $header, 'echoBlogCell', $config); ?>
<?php echoUOJPageFooter() ?>
