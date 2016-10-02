<?php
	function validateZan() {
		if (!validateUInt($_POST['id']))
			return false;
		if (!validateInt($_POST['delta']))
			return false;
		if ($_POST['delta'] != 1 && $_POST['delta'] != -1)
			return false;
		if ($_POST['type'] != 'B' && $_POST['type'] != 'BC' && $_POST['type'] != 'P' && $_POST['type'] != 'C')
			return false;
		return true;
	}
	if (!validateZan()) {
		die('<div class="text-danger">failed</div>');
	}
	if ($myUser == null) {
		die('<div class="text-danger">please <a href="'.HTML::url('/login').'">log in</a></div>');
	}
	
	$id = $_POST['id'];
	$delta = $_POST['delta'];
	$type = $_POST['type'];
	
	switch ($type) {
		case 'B':
			$table_name = 'blogs';
			break;
		case 'BC':
			$table_name = 'blogs_comments';
			break;
		case 'P':
			$table_name = 'problems';
			break;
		case 'C':
			$table_name = 'contests';
			break;
	}
	
	$cur = queryZanVal($id, $type, $myUser);
	
	if ($cur != $delta) {
		$row = mysql_fetch_array(mysql_query("select zan from $table_name where id = $id"));
		if ($row == null) {
			die('<div class="text-danger">failed</div>');
		}
		$cur += $delta;
		if ($cur == 0) {
			mysql_query("delete from click_zans where username = '{$myUser['username']}' and type = '$type' and target_id = $id");
		} else if ($cur != $delta) {
			mysql_query("update click_zans set val = '$cur' where username = '{$myUser['username']}' and type = '$type' and target_id = $id");
		} else {
			mysql_query("insert into click_zans (username, type, target_id, val) values ('{$myUser['username']}', '$type', $id, $cur)");
		}
		$cnt = $row['zan'] + $delta;
		mysql_query("update $table_name set zan = $cnt where id = $id");
	} else {
		$row = mysql_fetch_array(mysql_query("select zan from $table_name where id = $id"));
		if ($row == null) {
			die('<div class="text-danger">failed</div>');
		}
		$cnt = $row['zan'];
	}
?>
<?= getClickZanBlock($type, $id, $cnt, $cur) ?>
