<?php
	$username = $_GET['username'];
	
	requireLib('flot');
?>
<?php if (validateUsername($username) && ($user = UOJUser::query($username))): ?>
	<?php echoUOJPageHeader($user['username'] . ' - ' . UOJLocale::get('user profile')) ?>
    <?php uojIncludeView('user-info', ['user' => $user]); ?>
    <?php echoUOJPageFooter() ?>
<?php else: ?>
	<?php echoUOJPageHeader('不存在该用户' . ' - 用户信息') ?>
	<div class="panel panel-danger">
		<div class="panel-heading">用户信息</div>
		<div class="panel-body">
		<h4>不存在该用户</h4>
		</div>
	</div>
    <?php echoUOJPageFooter() ?>
<?php endif ?>