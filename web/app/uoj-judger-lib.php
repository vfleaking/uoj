<?php

function authenticateJudger() {
	if (!is_string($_POST['judger_name']) || !is_string($_POST['password'])) {
		return false;
	}
	$judger = DB::selectFirst([
		"select password from judger_info",
		"where", [
			"judger_name" => $_POST['judger_name'],
			"enabled" => true,
			"password" => $_POST['password']
		]
	]);
	return $judger != null;
}

function judgerCodeStr($code) {
	switch ($code) {
		case 0:
			return "Accepted";
		case 1:
			return "Wrong Answer";
		case 2:
			return "Runtime Error";
		case 3:
			return "Memory Limit Exceeded";
		case 4:
			return "Time Limit Exceeded";
		case 5:
			return "Output Limit Exceeded";
		case 6:
			return "Dangerous Syscalls";
		case 7:
			return "Judgment Failed";
		default:
			return "No Comment";
	}
}

/**
 * Better to use UOJProblemConf instead
 * 
 * @return array|int
 */
function getUOJConf($file_name) {
	$ret = UOJProblemConf::getFromFile($file_name);
	if ($ret instanceof UOJProblemConf) {
		return $ret->conf;
	} else {
		return $ret;
	}
}

/**
 * Better to use UOJProblemConf instead
 */
function putUOJConf($file_name, $conf) {
	(new UOJProblemConf($conf))->putToFile($file_name);
}

/**
 * Better to use UOJProblemConf instead
 */
function getUOJConfVal($conf, $key, $default_val) {
	return (new UOJProblemConf($conf))->getVal($key, $default_val);
}

function getUOJProblemInputFileName($problem_conf, $num) {
	return getUOJConfVal($problem_conf, 'input_pre', 'input') . $num . '.' . getUOJConfVal($problem_conf, 'input_suf', 'txt');
}
function getUOJProblemOutputFileName($problem_conf, $num) {
	return getUOJConfVal($problem_conf, 'output_pre', 'output') . $num . '.' . getUOJConfVal($problem_conf, 'output_suf', 'txt');
}
function getUOJProblemExtraInputFileName($problem_conf, $num) {
	return 'ex_' . getUOJConfVal($problem_conf, 'input_pre', 'input') . $num . '.' . getUOJConfVal($problem_conf, 'input_suf', 'txt');
}
function getUOJProblemExtraOutputFileName($problem_conf, $num) {
	return 'ex_' . getUOJConfVal($problem_conf, 'output_pre', 'output') . $num . '.' . getUOJConfVal($problem_conf, 'output_suf', 'txt');
}

function updateBestACSubmissions($username, $problem_id) {
	$update_best = function() use($username, $problem_id) {
		$best = DB::selectFirst([
			"select id, used_time, used_memory, tot_size from submissions",
			"where", [
				"submitter" => $username,
				"problem_id" => $problem_id,
				"score" => 100
			], "order by used_time, used_memory, tot_size asc limit 1", DB::for_share()
		]);
		if ($best) {
			$shortest = DB::selectFirst([
				"select id, used_time, used_memory, tot_size from submissions",
				"where", [
					"submitter" => $username,
					"problem_id" => $problem_id,
					"score" => 100
				], "order by tot_size, used_time, used_memory asc limit 1", DB::for_share()
			]);
			$keys = [
				'submission_id', 'used_time', 'used_memory', 'tot_size',
				'shortest_id', 'shortest_used_time', 'shortest_used_memory', 'shortest_tot_size'
			];
			$vals = [
				$best['id'], $best['used_time'], $best['used_memory'], $best['tot_size'],
				$shortest['id'], $shortest['used_time'], $shortest['used_memory'], $shortest['tot_size']
			];
			$fields_str = '(problem_id, submitter';
			for ($i = 0; $i < count($keys); $i++) {
				$fields_str .= ", {$keys[$i]}";
			}
			$fields_str .= ')';

			DB::insert([
				"insert into best_ac_submissions",
				$fields_str,
				"values", DB::tuple(array_merge([$problem_id, $username], $vals)),
				"on duplicate key update", array_combine($keys, $vals)
			]);
		} else {
			DB::delete([
				"delete from best_ac_submissions",
				"where", [
					"submitter" => $username,
					"problem_id" => $problem_id
				]
			]);
		}
	};

	DB::transaction($update_best);

	DB::update([
		"update user_info",
		"set", [
			"ac_num" => DB::rawbracket([
				"select count(*) from best_ac_submissions",
				"where", ["submitter" => $username]
			])
		], "where", ["username" => $username]
	]);
	
	DB::update([
		"update problems",
		"set", [
			"ac_num" => DB::rawbracket([
				"select count(*) from submissions",
				"where", [
					"problem_id" => DB::raw("problems.id"),
					"score" => 100
				]
			]),
			"submit_num" => DB::rawbracket([
				"select count(*) from submissions",
				"where", [
					"problem_id" => DB::raw("problems.id")
				]
			])
		], "where", ["id" => $problem_id]
	]);
}
