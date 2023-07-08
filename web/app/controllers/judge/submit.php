<?php
	requirePHPLib('judger');
	requirePHPLib('svn');
	
	if (!authenticateJudger()) {
		UOJResponse::page404();
	}
	
	function submissionJudged() {
        UOJSubmission::onJudged(UOJRequest::post('id'), UOJRequest::post('result'), UOJRequest::post('judge_time'));
	}

	function customTestSubmissionJudged() {
		$submission = DB::selectFirst([
            "select submitter, status, content, result, problem_id from custom_test_submissions",
            "where", ['id' => $_POST['id']]
        ]);
		if ($submission == null) {
			return;
		}
		if ($submission['status'] != 'Judging') {
			return;
		}
		$result = json_decode($_POST['result'], true);
		$result['details'] = uojTextEncode($result['details']);
        DB::update([
            "update custom_test_submissions",
            "set", [
                'status' => $result['status'],
                'status_details' => '',
                'result' => json_encode($result, JSON_UNESCAPED_UNICODE)
            ], "where", ['id' => $_POST['id']]
        ]);
	}
	
	function hackJudged() {
        $result = json_decode($_POST['result'], true);

        UOJHack::init($_POST['id']);
        UOJHack::cur()->setProblem();
        UOJHack::cur()->setSubmission();

        if ($result['score']) {
            $status = 'Judged, Waiting';
        } else {
            $status = 'Judged';
        }

		$ok = DB::update([
            "update hacks",
            "set", [
                'success' => $result['score'],
                'status' => $status,
                'details' => uojTextEncode($result['details'])
            ], "where", ['id' => $_POST['id']]
        ]);

        if (!$result['score']) {
            return;
        }
        if (!$ok) {
            return;
        }

        if (!(validateUploadedFile('hack_input') && validateUploadedFile('std_output'))) {
            UOJLog::error("hack successfully but received no data. id: {$_POST['id']}");
            return;
        }

        $input = UOJContext::storagePath().UOJHack::info('input');
        $up_in = $_FILES["hack_input"]['tmp_name'];
        $up_out = $_FILES["std_output"]['tmp_name'];

        if (!UOJHack::cur()->problem->needToReviewHack()) {
            $err = UOJHack::cur()->problem->addHackPoint($up_in, $up_out);
            if ($err === '') {
                unlink($input);
                DB::update([
                    "update hacks",
                    "set", [
                        'status' => 'Judged'
                    ], "where", ['id' => $_POST['id']]
                ]);
                return;
            } else {
                UOJLog::error("hack successfully but failed to add an extra test: {$err}");
            }
        }
        move_uploaded_file($up_in, "{$input}_in");
        move_uploaded_file($up_out, "{$input}_out");
        DB::update([
            "update hacks",
            "set", [
                'status' => 'Judged, WaitingM'
            ], "where", ['id' => $_POST['id']]
        ]);
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
        
        $status_details = $_POST['status'];
		if (isset($_POST['is_custom_test'])) {
			DB::update([
                "update custom_test_submissions",
                "set", ["status_details" => $status_details],
                "where", ["id" => $_POST['id']]
            ]);
		} else {
			DB::update([
                "update submissions",
                "set", ["status_details" => $status_details],
                "where", ["id" => $_POST['id']]
            ]);
		}
		die();
    }
    
    $problem_ban_list = array_map(fn ($x) => $x['id'], DB::selectAll([
        "select id from problems",
        "where", [
            ["assigned_to_judger", "!=", "any"],
            ["assigned_to_judger", "!=", $_POST['judger_name']]
        ]
    ]));
    $assignCond = $problem_ban_list ? [["submissions.problem_id", "not in", DB::rawtuple($problem_ban_list)]] : [];
	
	$submission = null;
	$hack = null;
	function querySubmissionToJudge($status, $set_q) {
        global $assignCond;

        for ($times = 0; $times < 10; $times++) {
            $submission = DB::selectFirst([
                "select submissions.id from submissions",
                "where", array_merge(["submissions.status" => $status], $assignCond),
                "order by id limit 1"
            ]);
            if (!$submission) {
                return null;
            }

            $ok = DB::transaction(function() use(&$submission, $set_q, $status) {
                DB::update([
                    "update submissions",
                    "set", $set_q,
                    "where", [
                        "id" => $submission['id'],
                        "status" => $status
                    ]
                ]);
                if (DB::affected_rows() == 1) {
                    $submission = DB::selectFirst([
                        "select id, problem_id, content, status, judge_time from submissions",
                        "where", ["id" => $submission['id']]
                    ]);
                    return true;
                } else {
                    return false;
                }
            });
            if ($ok) {
                return $submission;
            }
        }
	}
    function queryMinorSubmissionToJudge($status, $set_q) {
        global $assignCond;

        for ($times = 0; $times < 10; $times++) {
            $submission = null;
            if ($assignCond) {
                $his = DB::selectFirst([
                    "select submissions_history.id, submissions_history.submission_id from submissions_history",
                    "inner join submissions on submissions.id = submissions_history.submission_id",
                    "where", array_merge(
                        ["submissions_history.status" => $status, "submissions_history.major" => 0],
                        $assignCond
                    ),
                    "order by id limit 1"
                ]);
            } else {
                $his = DB::selectFirst([
                    "select id, submission_id from submissions_history",
                    "where", ["status" => $status, "major" => 0],
                    "order by id limit 1"
                ]);
            }
            if (!$his) {
                return null;
            }

            $ok = DB::transaction(function() use(&$submission, &$his, $set_q, $status) {
                $submission = DB::selectFirst([
                    "select id, problem_id, content from submissions",
                    "where", ["id" => $his['submission_id']], DB::for_share()
                ]);
                if (!$submission) {
                    return false;
                }
                DB::update([
                    "update submissions_history",
                    "set", $set_q,
                    "where", [
                        "id" => $his['id'],
                        "status" => $status
                    ]
                ]);
                if (DB::affected_rows() == 1) {
                    $ret = DB::selectFirst([
                        "select status, judge_time from submissions_history",
                        "where", ["id" => $his['id']]
                    ]);
                    if ($ret === false) {
                        return false;
                    }
                    $submission += $ret;
                    return true;
                } else {
                    return false;
                }
            });
            if ($ok) {
                return $submission;
            }
        }
    }
	function queryCustomTestSubmissionToJudge() {
        global $assignCond;

        while (true) {
            $submission = DB::selectFirst([
                "select id, problem_id, content from custom_test_submissions as submissions",
                "where", array_merge(["submissions.judge_time" => null], $assignCond),
                "order by id limit 1"
            ]);
            if (!$submission) {
                return null;
            }
            $submission['is_custom_test'] = '';

            DB::update([
                "update custom_test_submissions",
                "set", [
                    "judge_time" => DB::now(),
                    "status" => 'Judging'
                ], "where", [
                    "id" => $submission['id'],
                    "judge_time" => null
                ]
            ]);
            if (DB::affected_rows() == 1) {
                $submission['status'] = 'Judging';
                return $submission;
            }
        }
	}
	function queryHackToJudge() {
        global $assignCond;

        while (true) {
            if (DB::selectFirst([
                "select 1 from hacks",
                "where", [
                    ["status", "!=", "Waiting"],
                    ["status", "!=", "Judged"],
                ], "order by id limit 1"
            ])) {
                return null;
            }

            $hack = DB::selectFirst([
                "select hacks.id, hacks.submission_id, hacks.input, hacks.input_type from hacks",
				"inner join submissions on submissions.id = hacks.submission_id",
                "where", array_merge(["hacks.judge_time" => null], $assignCond),
                "order by id limit 1"
            ]);
            if (!$hack) {
                return null;
            }

            DB::update([
                "update hacks",
                "set", [
                    "judge_time" => DB::now(),
                    "status" => 'Judging'
                ],
                "where", [
                    "id" => $hack['id'],
                    "judge_time" => null
                ]
            ]);
            if (DB::affected_rows() == 1) {
                $hack['status'] = 'Judging';
                return $hack;
            }
        }
	}
	function findSubmissionToJudge() {
		global $submission, $hack;
		$submission = querySubmissionToJudge('Waiting', [
            "judge_time" => DB::now(),
            "judger" => $_POST['judger_name'],
            "status" => 'Judging'
        ]);
		if ($submission) {
			return true;
        }
        
        $submission = queryCustomTestSubmissionToJudge();
		if ($submission) {
			return true;
		}
		
		$submission = querySubmissionToJudge('Waiting Rejudge', [
            "judge_time" => DB::now(),
            "judger" => $_POST['judger_name'],
            "status" => 'Judging'
        ]);
		if ($submission) {
			return true;
		}
		
		$submission = querySubmissionToJudge('Judged, Waiting', [
            "status" => 'Judged, Judging'
        ]);
		if ($submission) {
			return true;
		}

		$submission = queryMinorSubmissionToJudge('Waiting Rejudge', [
            "judge_time" => DB::now(),
            "judger" => $_POST['judger_name'],
            "status" => 'Judging'
        ]);
		if ($submission) {
			return true;
		}
		
		$submission = queryMinorSubmissionToJudge('Judged, Waiting', [
            "status" => 'Judged, Judging'
        ]);
		if ($submission) {
			return true;
		}
		
		$hack = queryHackToJudge();
		if ($hack) {
			$submission = DB::selectFirst([
                "select id, problem_id, content from submissions",
                "where", [
                    "id" => $hack['submission_id'],
                    "score" => 100
                ]
            ]);
			if (!$submission) {
				$details = "<error>the score gained by the hacked submission is not 100.</error>";
				DB::update([
                    "update hacks",
                    "set", [
                        'success' => 0,
                        'status' => 'Judged',
                        'details' => uojTextEncode($details)
                    ], "where", ["id" => $hack['id']]
                ]);
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
	$submission['problem_mtime'] = filemtime("/var/uoj_data/{$submission['problem_id']}");
	$submission['content'] = json_decode($submission['content'], true);
    if (isset($submission['status']) && $submission['status'] == 'Judged, Judging' && isset($submission['content']['final_test_config'])) {
        $submission['content']['config'] = $submission['content']['final_test_config'];
		unset($submission['content']['final_test_config']);
    }

	if ($hack) {
		$submission['is_hack'] = "";
		$submission['hack']['id'] = (int)$hack['id'];
		$submission['hack']['input'] = $hack['input'];
		$submission['hack']['input_type'] = $hack['input_type'];
	}
	
	echo json_encode($submission);
?>
