<?php
    UOJContest::init(UOJRequest::get('id')) || UOJResponse::page404();

	$contest = UOJContest::info();
	
	$is_manager = UOJContest::cur()->userCanManage(Auth::user());
	$show_ip = $is_manager;
	
	if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
		$iHasRegistered = UOJContest::cur()->userHasRegistered(Auth::user());
	
		if ($iHasRegistered && UOJContest::cur()->freeRegistration()) {
			$unregister_form = new UOJForm('unregister');
			$unregister_form->handle = function() {
                UOJContest::cur()->userUnregister(Auth::user());
			};
			$unregister_form->submit_button_config['class_str'] = 'btn btn-danger btn-xs';
			$unregister_form->submit_button_config['text'] = '取消报名';
            $unregister_form->succ_href = "/contests";

			$unregister_form->runAtServer();
		}
		
		if ($is_manager) {
			$pre_rating_form = new UOJForm('pre_rating');
			$pre_rating_form->handle = function() {
				UOJContest::cur()->updatePreContestRating();
			};
			$pre_rating_form->submit_button_config['align'] = 'right';
			$pre_rating_form->submit_button_config['class_str'] = 'btn btn-warning';
			$pre_rating_form->submit_button_config['text'] = '重新计算参赛前的 rating';
			$pre_rating_form->submit_button_config['smart_confirm'] = '';
			
			$pre_rating_form->runAtServer();
		}
	}
?>
<?php echoUOJPageHeader(HTML::stripTags($contest['name']) . ' - ' . UOJLocale::get('contests::contest registrants')) ?>

<h1 class="text-center"><?= $contest['name'] ?></h1>
<?php if ($contest['cur_progress'] == CONTEST_NOT_STARTED): ?>
    <?php if ($iHasRegistered): ?>
        <?php if (isset($unregister_form)): ?>
            <div class="pull-right">
                <?php $unregister_form->printHTML(); ?>
            </div>
		<?php endif?>
		<div><a style="color:green">已报名</a></div>
	<?php elseif (UOJContest::cur()->freeRegistration()): ?>
		<div>当前尚未报名，您可以<a style="color:red" href="/contest/<?= $contest['id'] ?>/register">报名</a>。</div>
	<?php else: ?>
		<div>当前您尚未报名。</div>
	<?php endif ?>
<div class="top-buffer-sm"></div>
<?php endif ?>

<?php
	$header_row = '<tr><th>#</th><th>'.UOJLocale::get('username').'</th>';
	
	if ($contest['extra_config']['individual_or_team'] == 'team') {
		$header_row .= '<th>队伍名称</th>';
	}
	if ($show_ip) {
		$header_row .= '<th>remote_addr</th>';
		
        $ip_owner = array();
        $res = DB::selectAll([
            "select * from contests_registrants",
            "where", ["contest_id" => $contest['id']],
            "order by user_rating asc"
        ]);
		foreach ($res as $reg) {
			$user = UOJUser::query($reg['username']);
			$ip_owner[$user['remote_addr']] = $reg['username'];
		}
	}
	$header_row .= '<th>rating</th></tr>';
	
	echoLongTable(
        ['*'], 'contests_registrants',
        ["contest_id" => $contest['id']], 'order by user_rating desc, username asc',
		$header_row,
		function($reg, $num) {
			global $show_ip, $ip_owner;
			global $contest;
			
			$user = UOJUser::query($reg['username']);
			$user_link = getUserLink($reg['username'], $reg['user_rating']);
			if (!$show_ip) {
				echo '<tr>';
			} else {
				if ($ip_owner[$user['remote_addr']] != $user['username']) {
					echo '<tr class="danger">';
				} else {
					echo '<tr>';
				}
			}
			echo '<td>'.$num.'</td>';
			echo '<td>'.$user_link.'</td>';
			if ($contest['extra_config']['individual_or_team'] == 'team') {
				$extra = json_decode($user['extra'], true);
				if ($extra === null) {
					$extra = [];
				}
				if ($extra !== null && isset($extra['acm']) && isset($extra['acm']['team_name'])) {
					echo '<td>'.HTML::escape($extra['acm']['team_name']).'</td>';
				} else {
					echo '<td></td>';
				}
			}
			if ($show_ip) {
				echo '<td>'.$user['remote_addr'].'</td>';
			}
			echo '<td>'.$reg['user_rating'].'</td>';
			echo '</tr>';
		},
		array('page_len' => 100,
			'get_row_index' => '',
			'print_after_table' => function() {
				global $pre_rating_form;
				if (isset($pre_rating_form)) {
					$pre_rating_form->printHTML();
				}
			}
		)
	);
?>
<?php echoUOJPageFooter() ?>
