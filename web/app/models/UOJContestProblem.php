<?php

class UOJContestProblem extends UOJProblem {
    public $contest = null;
    public $problem_number = -1;

    public static function query($id, UOJContest $contest = null) {
        $problem = parent::query($id);
        if ($problem === null) {
            return $problem;
        }
        if ($contest === null) {
            $contest = UOJContest::cur();
        }
        return new UOJContestProblem($problem->info, $contest);
    }

    public function __construct($info, UOJContest $contest) {
        parent::__construct($info);
        $this->contest = $contest;
    }

    public function valid() {
        return $this->contest && $this->contest->hasProblem($this);
    }

    public function getProblemNumber() {
        if ($this->problem_number !== -1) {
            return $this->problem_number;
        }
	    $this->problem_number = DB::selectCount([
		    DB::lc(), "select count(*) from contests_problems",
		    "where", [
			    'contest_id' => $this->contest->info['id'],
			    ['problem_id', '<', $this->info['id']]
		    ]
        ]);
        return $this->problem_number;
    }

    public function getLetter() {
        $num = $this->getProblemNumber();
        if ($num === null) {
            return null;
        }
        return chr(ord('A') + $num);
    }

    public function getTitle(array $cfg = []) {
        $cfg += [
            'with' => 'id',
            'simplify' => false
        ];
        $title = parent::getTitle(['with' => null] + $cfg);
        if ($cfg['simplify']) {
            $letter = $this->getLetter();
            if (strStartWith($title, $letter)) {
                $title = substr($title, strlen($letter));
                if (strStartWith($title, '.')) {
                    $title = substr($title, 1);
                }
                $title = trim($title);
            }
        }
        if ($cfg['with'] == 'letter') {
            if (!isset($letter)) {
                $letter = $this->getLetter();
            }
            return "{$letter}. {$title}";
        } elseif ($cfg['with'] == 'id') {
            return "#{$this->info['id']}. {$title}";
        } else {
            return $title;
        }
    }

    public function getUri($where = '') {
        if (!$this->contest) {
            return false;
        }
	    return "/contest/{$this->contest->info['id']}/problem/{$this->info['id']}{$where}";
    }

    public function queryUserSubmissionCountInContest(array $user = null) {
        if (!$user) {
            return 0;
        }
        return DB::selectCount([
            DB::lc(), "select count(*) from submissions",
            "where", [
                "submitter" => $user['username'],
                "problem_id" => $this->info['id'],
                "contest_id" => $this->contest->info['id'],
                DB::lor([
                    [UOJSubmission::sqlForActualScore(), "is not", null],
                    ['status', 'not like', 'Judged%']
                ])
            ]
        ]);
    }

    public function getJudgeTypeInContest() {
        if (!isset($this->contest->info['extra_config']["problem_{$this->info['id']}"])) {
            return $this->contest->defaultProblemJudgeType();
        } else {
            return $this->contest->info['extra_config']["problem_{$this->info['id']}"];
        }
    }

    public function isBonus() {
        return isset($this->contest->info['extra_config']['bonus']["problem_{$this->info['id']}"]);
    }

    public function submitTimeLimit() {
        if (isset($this->contest->info['extra_config']['submit_time_limit']["problem_{$this->info['id']}"])) {
            return $this->contest->info['extra_config']['submit_time_limit']["problem_{$this->info['id']}"];
        } else {
            return -1;
        }
    }

    public function userCanClickZan(array $user = null) {
        return $this->userCanView($user) && $this->contest->progress() > CONTEST_IN_PROGRESS;
    }

    public function userCanView(array $user = null, array $cfg = []) {
        $cfg += ['check-register' => true];
        return $this->contest->userCanView($user, $cfg);
    }

    public function userCanDownloadAttachments(array $user = null) {
        return $this->userCanView($user);
    }

    public function preHackCheck(array $user = null) {
        return $this->info['hackable'] && !$this->info['is_hidden'];
    }

    public function preSubmitCheck() {
        if ($this->contest->userCanManage(Auth::user())) {
            return true;
        }
        if ($this->contest->progress() > CONTEST_IN_PROGRESS && !parent::userCanView(Auth::user())) {
            return '比赛已结束，请耐心等待管理员在题库公开题目后再提交哦～';
        }
        return true;
    }

    public function additionalSubmissionComponentsCannotBeSeenByUser(array $user = null, UOJSubmission $submission) {
        // excluding manager_view, score; don't check whether $user is a manager
        if ($this->contest->progress() == CONTEST_IN_PROGRESS) {
            if ($submission->userIsSubmitter($user)) {
                if ($this->getJudgeTypeInContest() == 'no-details') {
                    return ['low_level_details'];
                } else {
                    return [];
                }
            } else {
                return ['content', 'high_level_details', 'low_level_details'];
            }
        } else {
            return [];
        }
    }
}