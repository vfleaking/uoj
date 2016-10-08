<?php

if (DB::checkTableExists('upgrades')) {
	if (!Auth::check() || !isSuperUser(Auth::user())) {
		become403Page();
	}
}

header('Content-Type:text/plain');

switch ($_GET['type']) {
	case 'up':
		Upgrader::up($_GET['upgrade_name']);
		break;
	case 'down':
		Upgrader::down($_GET['upgrade_name']);
		break;
	case 'latest':
		Upgrader::upgradeToLatest();
		break;
}

die("finished!\n");
