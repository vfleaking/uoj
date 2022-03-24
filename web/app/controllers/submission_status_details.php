<?php

Auth::check() || UOJResponse::page403();
is_array($_GET['get']) || UOJResponse::page404();

$res = [];
foreach ($_GET['get'] as $id) {
    ($submission = UOJSubmission::query($id)) || UOJResponse::page404();
    $submission->setProblem() || UOJResponse::page404();
    $submission->userIsSubmitter(Auth::user()) || UOJResponse::page403();
    $submission->userCanView(Auth::user(), ['ensure' => true]);
	$res[] = [
		'judged' => $submission->hasJudged(),
		'waiting' => $submission->isWaiting(),
		'html' => $submission->getStatusDetailsHTML()
	];
}

die(json_encode($res));
