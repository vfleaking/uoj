<?php
	if ($myUser == null) {
		redirectToLogin();
	}
	
	$header_row = <<<EOD
<tr>
	<th>消息</th>
	<th style="width:15em">时间</th>
</tr>
EOD;
	function echoSysMsg($msg) {
		echo $msg['read_time'] == null ? '<tr class="warning">' : '<tr>';
		echo '<td>';
		echo '<h4>'.$msg['title'].'</h4>';
		echo $msg['content'];
		echo '</td>';
		echo '<td>'.$msg['send_time'].'</td>';
		echo '</tr>';
	}
?>
<?php echoUOJPageHeader('系统消息') ?>
<h2>系统消息</h2>
<?php echoLongTable(array('*'), 'user_system_msg', "receiver='" . Auth::id() . "'", 'order by id desc', $header_row, 'echoSysMsg', array('table_classes' => array('table'))) ?>
<?php DB::update("update user_system_msg set read_time = now() where receiver = '" . Auth::id() . "'") ?>
<?php echoUOJPageFooter() ?>
