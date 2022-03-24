<?php

switch (UOJRequest::get('type')) {
	case 'rating':
		$title = UOJLocale::get('top rated - all users');
		$html = '<h3>'.UOJLocale::get('top rated > all users').'</h3>';
		$cfg = [];
		break;
	case 'active-users-rating':
		$title = UOJLocale::get('top rated - active users');
		$html = '<h3>'.UOJLocale::get('top rated > active users').'</h3>';
		$html .= '<p>'.UOJLocale::get('active rule', UOJContext::getMeta('active_duration_M')).'</p>';
		$cfg = ['active_users_only' => true];
		break;
	default:
		UOJResponse::page404();
}

?>
<?php echoUOJPageHeader($title) ?>
<?= $html ?>
<?php UOJRanklist::printHTML($cfg) ?>
<?php echoUOJPageFooter() ?>
