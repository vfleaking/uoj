<?php
	requirePHPLib('judger');
	switch ($_GET['type']) {
		case 'problem':
			if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
				become404Page();
			}
			
			$visible = isProblemVisibleToUser($problem, $myUser);
			if (!$visible && $myUser != null) {
				$result = mysql_query("select contest_id from contests_problems where problem_id = {$_GET['id']}");
				while (list($contest_id) = mysql_fetch_array($result, MYSQL_NUM)) {
					$contest = queryContest($contest_id);
					genMoreContestInfo($contest);
					if ($contest['cur_progress'] != CONTEST_NOT_STARTED && hasRegistered($myUser, $contest) && queryContestProblemRank($contest, $problem)) {
						$visible = true;
					}
				}
			}
			if (!$visible) {
				become404Page();
			}

			$id = $_GET['id'];
			
			$file_name = "/var/uoj_data/$id/download.zip";
			$download_name = "problem_$id.zip";
			break;
		case 'testlib.h':
			$file_name = "/home/local_main_judger/judge_client/uoj_judger/include/testlib.h";
			$download_name = "testlib.h";
			break;
		default:
			become404Page();
	}
	
	$finfo = finfo_open(FILEINFO_MIME);
	$mimetype = finfo_file($finfo, $file_name);
	if ($mimetype === false) {
		become404Page();
	}
	finfo_close($finfo);
	
	header("X-Sendfile: $file_name");
	header("Content-type: $mimetype");
	header("Content-Disposition: attachment; filename=$download_name");
?>
