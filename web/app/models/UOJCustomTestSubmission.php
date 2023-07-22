<?php

class UOJCustomTestSubmission {
    use UOJSubmissionLikeTrait;

    public static function query(UOJProblem $problem, array $user = null) {
        if (!$user) {
            return null;
        }
        $info = DB::selectFirst([
		    "select * from custom_test_submissions",
		    "where", [
			    "submitter" => $user['username'],
			    "problem_id" => $problem->info['id']
		    ], "order by id desc limit 1"
        ]);
        if (!$info) {
            return null;
        }
        $subm = new UOJCustomTestSubmission($info);
        return $subm->setProblem(['problem' => $problem]) ? $subm : null;
    }

    public function __construct($info) {
        $this->info = $info;
    }

    public static function onUpload(UOJSubmissionArchive $archive) {
		$archive->content['config'][] = ['problem_id', UOJProblem::info('id')];
		$archive->content['config'][] = ['custom_test', 'on'];
        $content_json = json_encode($archive->content);
        
        static::getAndRememberSubmissionLanguage($archive->content);

		$result = ['status' => "Waiting"];
		$result_json = json_encode($result);
		
        $qs = [
            "insert into custom_test_submissions",
            "(problem_id, submit_time, submitter, content, status, result)",
            "values", DB::tuple([
                UOJProblem::info('id'), DB::now(), Auth::id(), $content_json,
                $result['status'], $result_json
            ])
        ];
        $ret = retry_loop(fn() => DB::insert($qs));
        if ($ret === false) {
            $archive->unlink();
            UOJLog::error('submission failed.');
        }
    }
}