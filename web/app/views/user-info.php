<?php

$esc_email = HTML::escape($user['email']);
$esc_qq = HTML::escape($user['qq'] != 0 ? $user['qq'] : 'Unfilled');
$esc_sex = HTML::escape($user['sex']);
$col_sex="color:blue";
if($esc_sex == "M") {
    $esc_sex="♂";
    $col_sex="color:blue";
}
else if($esc_sex == "F") {
    $esc_sex="♀";
    $col_sex="color:red";
} else {
    $esc_sex="";
    $col_sex="color:black";
}
$esc_motto = HTML::escape($user['motto']);

$extra = UOJUser::getExtra($user);
UOJUser::sortExtraVisitHistory($extra['history']);

$ac_problems = DB::selectAll([
    "select * from problems",
    "where", [
        [
            "id", "in", DB::rawbracket([
                "select problem_id from best_ac_submissions",
                "where", ["submitter" => $user['username']]
            ])
        ],
        UOJProblem::sqlForUserCanView(Auth::user())
    ],
    "order by id"
]);

$rating_his = UOJUser::getRatingHistory($user);
$rating_plot_min = $rating_his['rating_min'] - 400;
$rating_plot_max = $rating_his['rating_max'] + 400;
$time_plot_min = clone $rating_his['time_min'];
$time_plot_min->modify('first day of last month');
$time_plot_min = ($time_plot_min->getTimestamp() + $time_plot_min->getOffset()) * 1000;
$time_plot_max = clone $rating_his['time_max'];
$time_plot_max->modify('last day of next month');
$time_plot_max = ($time_plot_max->getTimestamp() + $time_plot_max->getOffset()) * 1000;

$perm = UOJUser::viewerCanSeeComponents($user, Auth::user());

?>
<div class="panel panel-info">
    <div class="panel-heading">
        <h2 class="panel-title"><?= uojlocale::get('user profile') ?></h2>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-4 col-md-push-8">
                <img class="media-object img-thumbnail center-block" alt="<?= $user['username'] ?> Avatar" src="<?= HTML::avatar_addr($user, 256) ?>" />
            </div>
            <div class="col-md-8 col-md-pull-4">
                <h2><span class="uoj-honor" data-rating="<?= $user['rating'] ?>"><?= $user['username'] ?></span> <span><strong style="<?= $col_sex ?>"><?= $esc_sex ?></strong></span></h2>
                <div class="list-group">
                    <div class="list-group-item">
                        <h4 class="list-group-item-heading"><?= UOJLocale::get('rating') ?></h4>
                        <p class="list-group-item-text"><strong style="color:red"><?= $user['rating'] ?></strong></p>
                    </div>
                    <?php if ($perm['email']): ?>
                    <div class="list-group-item">
                        <h4 class="list-group-item-heading"><?= UOJLocale::get('email') ?><?= UOJUser::getVisibilityHTML($extra['show_email']) ?></h4>
                        <p class="list-group-item-text"><?= $esc_email ?></p>
                    </div>
                    <?php endif ?>
                    <?php if ($perm['qq']): ?>
                    <div class="list-group-item">
                        <h4 class="list-group-item-heading"><?= UOJLocale::get('QQ') ?><?= UOJUser::$visibility_codes[$extra['show_qq']]['html'] ?></h4>
                        <p class="list-group-item-text"><?= $esc_qq ?></p>
                    </div>
                    <?php endif ?>
                    <div class="list-group-item">
                        <h4 class="list-group-item-heading"><?= UOJLocale::get('motto') ?></h4>
                        <p class="list-group-item-text"><?= $esc_motto ?></p>
                    </div>
                    <?php if (isTmpUser($user)): ?>
                        <?php if (isset($extra['acm'])): ?>
                        <div class="list-group-item">
                            <h4 class="list-group-item-heading"><?= HTML::escape($extra['acm']['contest_name']) ?>参赛队伍：<?= HTML::escape($extra['acm']['team_name']) ?></h4>
                            <?php foreach ($extra['acm']['members'] as $mem): ?>
                                <p class="list-group-item-text"><span style="display:inline-block; width:5em;"><?= HTML::escape($mem['name']) ?></span>（<?= HTML::escape($mem['organization']) ?>）</p>
                            <?php endforeach ?>
                        </div>
                        <?php endif ?>
                        <?php if (Auth::id() == $user['username'] || isSuperUser(Auth::user())): ?>
                        <div class="list-group-item">
                            <h4 class="list-group-item-heading">用户账户过期时间<?= UOJUser::getVisibilityHTML('self') ?></h4>
                            <p class="list-group-item-text"><?= $user['expiration_time'] ?></p>
                        </div>
                        <?php endif ?>
                    <?php endif ?>
                </div>
            </div>
        </div>
        <?php if (Auth::check()): ?>
        <?php if (Auth::id() != $user['username']): ?>
        <a type="button" class="btn btn-info btn-sm" href="<?= HTML::url("/user/msg?enter={$user['username']}") ?>"><span class="glyphicon glyphicon-envelope"></span> <?= UOJLocale::get('send private message') ?></a>
        <?php else: ?>
        <a type="button" class="btn btn-info btn-sm" href="<?= HTML::url("/user/modify-profile") ?>"><span class="glyphicon glyphicon-pencil"></span> <?= UOJLocale::get('modify my profile') ?></a>
        <?php endif ?>
        <?php endif ?>
        
        <a type="button" class="btn btn-success btn-sm" href="<?= HTML::blog_url($user['username'], '/') ?>"><span class="glyphicon glyphicon-arrow-right"></span> <?= UOJLocale::get('visit his blog', $user['username']) ?></a>
        
        <div class="top-buffer-lg"></div>
        <div class="list-group">
            <div class="list-group-item">
                <h4 class="list-group-item-heading"><?= UOJLocale::get('rating changes') ?></h4>
                <div class="list-group-item-text" id="rating-plot" style="height:500px;"></div>
            </div>
            <div class="list-group-item">
                <h4 class="list-group-item-heading"><?= UOJLocale::get('accepted problems').'：'.UOJLocale::get('n problems in total', count($ac_problems))?> </h4>
                <ul class="list-group-item-text nav nav-pills uoj-ac-problems-list">
                <?php
                foreach ($ac_problems as $problem) {
                    $uproblem = new UOJProblem($problem);
                    echo '<li>', $uproblem->getLink(), '</li>';
                }
                if (empty($ac_problems)) {
                    echo UOJLocale::get('none');
                }
                ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php if (isSuperUser(Auth::user())): ?>
<div class="panel panel-info">
    <div class="panel-heading">
        <h2 class="panel-title">仅超级管理员可见信息</h2>
    </div>
    <div class="panel-body">
    <h2><span class="uoj-honor" data-rating="<?= $user['rating'] ?>"><?= $user['username'] ?></span> <span><strong style="<?= $col_sex ?>"><?= $esc_sex ?></strong></span></h2>
    <div class="list-group">
        <div class="list-group-item">
            <h4 class="list-group-item-heading">注册时间</h4>
            <p class="list-group-item-text"><?= $user['register_time'] ?></p>
        </div>
        <div class="list-group-item">
            <h4 class="list-group-item-heading">最后一次登录的 IP（remote_addr）</h4>
            <p class="list-group-item-text"><?= $user['remote_addr'] ?></p>
        </div>
        <div class="list-group-item">
            <h4 class="list-group-item-heading">最后一次登录时间</h4>
            <p class="list-group-item-text"><?= $user['last_login_time'] ?></p>
        </div>
        <div class="list-group-item">
            <h4 class="list-group-item-heading">http_x_forwarded_for</h4>
            <p class="list-group-item-text"><?= HTML::escape($user['http_x_forwarded_for']) ?></p>
        </div>
        <div class="list-group-item">
            <h4 class="list-group-item-heading">最近访问 UOJ 时使用的 IP 和终端</h4>
            <dl class="list-group-item-text dl-horizontal">
            <?php foreach ($extra['history'] as $vis): ?>
                <dt><?= HTML::escape($vis['last']) ?></dt><dd><?= HTML::escape($vis['addr']) ?> (<?= HTML::escape($vis['ua']) ?>)</dd>
            <?php endforeach ?>
            </dl>
        </div>
    </div>
    </div>
</div>
<?php endif ?>

<script type="text/javascript">
var rating_data = [<?= json_encode($rating_his['data']) ?>];
var rating_plot = $.plot($("#rating-plot"), [{
	color: "#3850eb",
	label: "<?= $user['username'] ?>",
	data: rating_data[0]
}], {
	series: {
		lines: {
			show: true
		},
		points: {
			show: true
		}
	},
	xaxis: {
		mode: "time",
        minTickSize: [1, "month"],
        timeformat: "%Y-%m",
        min: <?= (int)$time_plot_min ?>,
        max: <?= (int)$time_plot_max ?>,
	},
	yaxis: {
		min: <?= (int)$rating_plot_min ?>,
		max: <?= (int)$rating_plot_max ?>,
	},
	legend: {
		labelFormatter: function(username) {
            // UOJ no longer uses the old formatter because it is hard to see when the rating is around 2100 : return getUserLink(username, <?= (int)$user['rating'] ?>, false);
	        return '&nbsp;' + username + '&nbsp;';
		}
	},
	grid: {
		clickable: true,
		hoverable: true,
	},
	hooks: {
		drawBackground: [
			function(plot, ctx) {
				var plotOffset = plot.getPlotOffset();
				for (var y = 0; y < plot.height(); y++) {
					var rating = <?= (int)$rating_plot_max ?> - <?= (int)($rating_plot_max - $rating_plot_min) ?> * y / plot.height();
					ctx.fillStyle = getColOfRating(rating);
					ctx.fillRect(plotOffset.left, plotOffset.top + y, plot.width(), Math.min(5, plot.height() - y));
				}
			}
		]
	}
});

function showTooltip(x, y, contents) {
    $('<div id="rating-tooltip">' + contents + '</div>').css({
        position: 'absolute',
        display: 'none',
        top: y - 20,
        left: x + 10,
        border: '1px solid #fdd',
        padding: '2px',
        'font-size' : '11px',
        'background-color': '#fee',
        opacity: 0.80
    }).appendTo("body").fadeIn(200);
}

var prev = -1;
function onHoverRating(event, pos, item) {
	if (prev != item.dataIndex) {
		$("#rating-tooltip").remove();
		var params = rating_data[item.seriesIndex][item.dataIndex];

		var total = params[1];
		var contestId = params[2];
		if (contestId != 0) {
			var change = params[6] >= 0 ? "+" + params[6] : params[6];
			var contestName = params[3];
			var rank = params[4];
			var html = "= " + total + " (" + change + "), <br/>"
			+ "Rank: " + rank + "<br/>"
            + '<a href="' + uojHome + '/contest/' + contestId + '">' + contestName + '</a>' + '<br/>'
            + '(' + params[5] + ')';
		} else {
			var html = "= " + total + "<br/>"
			+ "Unrated" + '<br/>'
            + '(' + params[5] + ')';
		}
		showTooltip(item.pageX, item.pageY, html);
		prev = item.dataIndex;
	}
}
$("#rating-plot").bind("plothover", function (event, pos, item) {
    if (item) {
    	onHoverRating(event, pos, item);
    }
});
$("#rating-plot").bind("plotclick", function (event, pos, item) {
    if (item && prev == -1) {
    	onHoverRating(event, pos, item);
    } else {
		$("#rating-tooltip").fadeOut(200);
		prev = -1;
	}
});
</script>
