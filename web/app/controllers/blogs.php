<?php

requirePHPLib('form');

if (Cookie::get('blogs_sortby') === 'active') {
	$sortby = 'active_time';
} else {
	$sortby = 'post_time';
}

function echoBlogCell(UOJBlog $blog) {
	echo '<tr>';
	
	$cnt_cmt = DB::selectCount([
		'select count(*) from blogs_comments',
		'where', ['blog_id' => $blog->info['id']]
	]);
	
	echo '<td>';
	echo '<div><span class="glyphicon glyphicon-comment"></span> '.$cnt_cmt.'</div>';
	echo '</td>';
	echo '<td>';
	echo '<div>'.ClickZans::getCntBlock($blog->info['zan']).'</div>';
	echo '</td>';
	
	echo '<td>' . $blog->getLink() . '</td>';
	echo '<td><div>' . $blog->info['post_time'] . '</div><div>'. getUserLink($blog->info['poster']) . '</div></td>';
	$newest = $blog->queryNewestComment();
	if ($newest) {
		echo '<td><div>' . $newest['post_time'] . '</div><div>' . getUserLink($newest['poster']) . '</div></td>';
	} else {
		echo '<td></td>';
	}
	echo '</tr>';
}
$header = '<tr>';
$header .= '<th style="width: 60px">评论</th>';
$header .= '<th style="width: 60px">评价</th>';
$header .= '<th>标题</th>';
$header .= '<th style="width: 170px">';
$header .= '<button id="input-sortby-post" class="btn ';
if ($sortby == 'post_time') {
	$header .= 'btn-primary';
} else {
	$header .= 'btn-default';
}
$header .= ' btn-xs"><span class="glyphicon glyphicon-sort-by-attributes-alt"></span></button> 发表时间';
$header .= '</th>';

$header .= '<th style="width: 170px">';
$header .= '<button id="input-sortby-active" class="btn ';
if ($sortby == 'active_time') {
	$header .= 'btn-primary';
} else {
	$header .= 'btn-default';
}
$header .= ' btn-xs"><span class="glyphicon glyphicon-sort-by-attributes-alt"></span></button> 最新评论';
$header .= '</th>';

$header .= '</tr>';

$config = [];
$config['table_classes'] = ['table', 'table-hover', 'table-vertical-middle'];

?>
<?php echoUOJPageHeader(UOJLocale::get('blogs')) ?>

<?php if (Auth::check()): ?>
<div class="pull-right">
	<a href="<?= HTML::blog_url(Auth::id(), '/') ?>" class="btn btn-info btn-sm">我的博客首页</a>
</div>
<?php endif ?>
<h3>博客总览</h3>
<?php
    echoLongTable(
        ['id', 'poster', 'title', 'post_time', 'active_time', 'zan'], 'blogs',
        ['is_hidden' => 0],
        'order by '.$sortby.' desc',
        $header, function($info) {
			echoBlogCell(new UOJBlog($info));
		}, $config
    );
?>

<script type="text/javascript">
	$('#input-sortby-post').click(function() {
		$.removeCookie('blogs_sortby', {path: '/blogs'});
		location.reload();
	});
	$('#input-sortby-active').click(function() {
		$.cookie('blogs_sortby', 'active', {path: '/blogs', expires: 365});
		location.reload();
	});
</script>

<?php echoUOJPageFooter() ?>
