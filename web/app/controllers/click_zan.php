<?php
	function validateZan() {
		if (!validateUInt($_POST['id']))
			return false;
		if (!validateInt($_POST['delta']))
			return false;
		if ($_POST['delta'] != 1 && $_POST['delta'] != -1)
			return false;
		if (!ClickZans::getTable($_POST['type']))
			return false;
		return true;
	}
	if (!validateZan()) {
		die('<div class="text-danger">failed</div>');
	}
	if (!Auth::check()) {
		die('<div class="text-danger">please <a href="'.HTML::url('/login').'">log in</a></div>');
	}
	if (!ClickZans::canClickZan($_POST['id'], $_POST['type'], Auth::user())) {
		die('<div class="text-danger">no permission</div>');
	}
	
	die(ClickZans::click($_POST['id'], $_POST['type'], Auth::user(), $_POST['delta']));
?>
