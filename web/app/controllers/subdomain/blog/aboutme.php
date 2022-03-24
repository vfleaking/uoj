<?php requireLib('flot') ?>
<?php echoUOJPageHeader('关于我') ?>
<?php uojIncludeView('user-info', ['user' => UOJUserBlog::user()]); ?>
<?php echoUOJPageFooter() ?>
