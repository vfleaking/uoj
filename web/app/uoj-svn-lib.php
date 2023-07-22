<?php

function svnNewProblem($id) {
	UOJLocalRun::exec(['/var/svn/problem/new_problem.sh', $id]);
	svnRefreshPasswordOfProblem($id);
	
	UOJLocalRun::execAnd([
		['cd', '/var/uoj_data'],
		['rm', '-f', "$id.zip"],
		['zip', "$id.zip", $id, '-r', '-q']
	]);
}
function svnRefreshPasswordOfProblem($id) {
	$res = DB::selectAll([
		"select user_info.username, svn_password from problems_permissions, user_info",
		"where", [
			"problem_id" => $id,
			"user_info.username" => DB::raw("problems_permissions.username")
		]
	], DB::NUM);
	$content = "[users]\n";
	$content .= UOJConfig::$data['svn']['our-root']['username']." = ".UOJConfig::$data['svn']['our-root']['password']."\n";
	foreach ($res as $row) {
		$content .= $row[0]." = ".$row[1]."\n";
	}
	file_put_contents("/var/svn/problem/$id/conf/passwd", $content);
}

function svnClearProblemData($problem) {
	$id = $problem['id'];
	if (!validateUInt($id)) {
		UOJLog::error("svnClearProblemData: hacker detected");
		return "invalid problem id";
	}
	
	UOJLocalRun::exec(['rm', "/var/svn/problem/$id", '-r']);
	UOJLocalRun::exec(['rm', "/var/uoj_data/$id", '-r']);
	svnNewProblem($id);
}
