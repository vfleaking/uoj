<?php
	requirePHPLib('judger');
	requirePHPLib('svn');
	
	if (!authenticateJudger()) {
		become404Page();
	}
	
	function submissionJudged() {
		$submission = DB::selectFirst("select submitter, status, content, result, problem_id from submissions where id = {$_POST['id']}");
		if ($submission == null) {
			return;
		}
		if ($submission['status'] != 'Judging' && $submission['status'] != 'Judged, Judging') {
			return;
		}
		$content = json_decode($submission['content'], true);
		
		if (isset($content['first_test_config'])) {
			$result = json_decode($submission['result'], true);
			$result['final_result'] = json_decode($_POST['result'], true);
			$result['final_result']['details'] = uojTextEncode($result['final_result']['details']);
			$esc_result = DB::escape(json_encode($result, JSON_UNESCAPED_UNICODE));
			
			$content['final_test_config'] = $content['config'];
			$content['config'] = $content['first_test_config'];
			unset($content['first_test_config']);
			$esc_content = DB::escape(json_encode($content));
			
			DB::update("update submissions set status = 'Judged', result = '$esc_result', content = '$esc_content' where id = {$_POST['id']}");
		} else {
			$result = json_decode($_POST['result'], true);
			$result['details'] = uojTextEncode($result['details']);
			$esc_result = DB::escape(json_encode($result, JSON_UNESCAPED_UNICODE));
			if (isset($result["error"])) {
				DB::update("update submissions set status = '{$result['status']}', result_error = '{$result['error']}', result = '$esc_result', score = null, used_time = null, used_memory = null where id = {$_POST['id']}");
			} else {
				DB::update("update submissions set status = '{$result['status']}', result_error = null, result = '$esc_result', score = {$result['score']}, used_time = {$result['time']}, used_memory = {$result['memory']} where id = {$_POST['id']}");
			}
			
			if (isset($content['final_test_config'])) {
				$content['first_test_config'] = $content['config'];
				$content['config'] = $content['final_test_config'];
				unset($content['final_test_config']);
				$esc_content = mysql_real_escape_string(json_encode($content));
			
				DB::update("update submissions set status = 'Judged, Waiting', content = '$esc_content' where id = ${_POST['id']}");
			}
		}
		DB::update("update submissions set status_details = '' where id = {$_POST['id']}");
		updateBestACSubmissions($submission['submitter'], $submission['problem_id']);
	}

	function customTestSubmissionJudged() {
		$submission = DB::selectFirst("select submitter, status, content, result, problem_id from custom_test_submissions where id = {$_POST['id']}");
		if ($submission == null) {
			return;
		}
		if ($submission['status'] != 'Judging') {
			return;
		}
		$content = json_decode($submission['content'], true);
		$result = json_decode($_POST['result'], true);
		$result['details'] = uojTextEncode($result['details']);
		$esc_result = DB::escape(json_encode($result, JSON_UNESCAPED_UNICODE));
		if (isset($result["error"])) {
			DB::update("update custom_test_submissions set status = '{$result['status']}', result = '$esc_result' where id = {$_POST['id']}");
		} else {
			DB::update("update custom_test_submissions set status = '{$result['status']}', result = '$esc_result' where id = {$_POST['id']}");
		}
		DB::update("update custom_test_submissions set status_details = '' where id = {$_POST['id']}");
	}
	
	function hackJudged() {
		$result = json_decode($_POST['result'], true);
		$esc_details = DB::escape(uojTextEncode($result['details']));
		$ok = DB::update("update hacks set success = {$result['score']}, details = '$esc_details' where id = {$_POST['id']}");
		
		if ($ok) {
			list($hack_input) = mysql_fetch_array(mysql_query("select input from hacks where id = {$_POST['id']}"), MYSQL_NUM);
			unlink(UOJContext::storagePath().$hack_input);

			if ($result['score']) {
				list($problem_id) = mysql_fetch_array(mysql_query("select problem_id from hacks where id = ${_POST['id']}"), MYSQL_NUM);
				if (validateUploadedFile('hack_input') && validateUploadedFile('std_output')) {
					svnAddExtraTest(queryProblemBrief($problem_id), $_FILES["hack_input"]["tmp_name"], $_FILES["std_output"]["tmp_name"]);
				} else {
					error_log("hack successfully but received no data. id: ${_POST['id']}");
				}
			}
		}
	}
	
	if (isset($_POST['submit'])) {
		if (!validateUInt($_POST['id'])) {
			die("Wow! hacker! T_T....");
		}
		if (isset($_POST['is_hack'])) {
			hackJudged();
		} elseif (isset($_POST['is_custom_test'])) {
			customTestSubmissionJudged();
		} else {
			submissionJudged();
		}
	}
	if (isset($_POST['update-status'])) {
		if (!validateUInt($_POST['id'])) {
			die("Wow! hacker! T_T....");
		}
		$esc_status_details = DB::escape($_POST['status']);
		if (isset($_POST['is_custom_test'])) {
			DB::update("update custom_test_submissions set status_details = '$esc_status_details' where id = {$_POST['id']}");
		} else {
			DB::update("update submissions set status_details = '$esc_status_details' where id = {$_POST['id']}");
		}
		die();
	}
	
	$submission = null;
	$hack = null;
	function querySubmissionToJudge($status, $set_q) {
		global $submission;
		$submission = DB::selectFirst("select id, problem_id, content from submissions where status = '$status' order by id limit 1");
		if ($submission) {
			DB::update("update submissions set $set_q where id = {$submission['id']} and status = '$status'");
			if (DB::affected_rows() != 1) {
				$submission = null;
			}
		}
	}
	function queryCustomTestSubmissionToJudge() {
		global $submission;
		$submission = DB::selectFirst("select id, problem_id, content from custom_test_submissions where judge_time is null order by id limit 1");
		if ($submission) {
			DB::update("update custom_test_submissions set judge_time = now(), status = 'Judging' where id = {$submission['id']} and judge_time is null");
			if (DB::affected_rows() != 1) {
				$submission = null;
			}
		}
		if ($submission) {
			$submission['is_custom_test'] = '';
		}
	}
	function queryHackToJudge() {
		global $hack;
		$hack = DB::selectFirst("select id, submission_id, input, input_type from hacks where judge_time is null order by id limit 1");
		if ($hack) {
			DB::update("update hacks set judge_time = now() where id = {$hack['id']} and judge_time is null");
			if (DB::affected_rows() != 1) {
				$hack = null;
			}
		}
	}
	function findSubmissionToJudge() {
		global $submission, $hack;
		querySubmissionToJudge('Waiting', "judge_time = now(), status = 'Judging'");
		if ($submission) {
			return true;
		}

		queryCustomTestSubmissionToJudge();
		if ($submission) {
			return true;
		}
		
		querySubmissionToJudge('Waiting Rejudge', "judge_time = now(), status = 'Judging'");
		if ($submission) {
			return true;
		}
		
		querySubmissionToJudge('Judged, Waiting', "status = 'Judged, Judging'");
		if ($submission) {
			return true;
		}
		
		queryHackToJudge();
		if ($hack) {
			$submission = DB::selectFirst("select id, problem_id, content from submissions where id = {$hack['submission_id']} and score = 100");
			if (!$submission) {
				$details = "<error>the score gained by the hacked submission is not 100.\n</error>";
				$esc_details = DB::escape(uojTextEncode($details));
				DB::update("update hacks set success = 0, details = '$esc_details' where id = {$hack['id']}");
				return false;
			}
			return true;
		}
		return false;
	}
	
	
	
	if (isset($_POST['fetch_new']) && !$_POST['fetch_new']) {
		die("Nothing to judge");
	}
	if (!findSubmissionToJudge()) {
		die("Nothing to judge");
	}
	
	$submission['id'] = (int)$submission['id'];
	$submission['problem_id'] = (int)$submission['problem_id'];
	$submission['problem_mtime'] = filemtime("/var/uoj_data/{$submission['problem_id']}.zip");
	$submission['content'] = json_decode($submission['content']);
	
	if ($hack) {
		$submission['is_hack'] = "";
		$submission['hack']['id'] = (int)$hack['id'];
		$submission['hack']['input'] = $hack['input'];
		$submission['hack']['input_type'] = $hack['input_type'];
	}
	
	echo json_encode($submission);
?>
