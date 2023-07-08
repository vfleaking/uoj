<?php

class UOJContest {
    use UOJDataTrait;

    public static function query($id) {
        if (!isset($id) || !validateUInt($id)) {
            return null;
        }
        $info = DB::selectFirst([
            "select * from contests",
            "where", ['id' => $id]
        ]);
        if (!$info) {
            return null;
        }
        return new UOJContest($info);
    }

    public static function finalTest() {
        $contest = self::info();

        $res = DB::selectAll([
            "select id, problem_id, content, submitter, hide_score_to_others from submissions",
            "where", ["contest_id" => $contest['id']]
        ]);
        foreach ($res as $submission) {
            $content = json_decode($submission['content'], true);
            if (isset($content['final_test_config'])) {
                $content['config'] = $content['final_test_config'];
                unset($content['final_test_config']);
            } else {
                continue;
            }
            UOJSubmission::rejudgeById($submission['id'], [
                'reason_text' => HTML::stripTags($contest['name']).' 最终测试',
                'reason_url' => HTML::url(UOJContest::cur()->getUri()),
                'set_q' => [
                    "content" => json_encode($content)
                ]
            ]);
        }
        
        // warning: check if this command works well when the database is not MySQL
        DB::update([
            "update submissions",
            "set", [
                "score = hidden_score",
                "hidden_score = NULL",
                "hide_score_to_others = 0"
            ], "where", [
                "contest_id" => $contest['id'],
                "hide_score_to_others" => 1
            ]
        ]);
        
        $updated = [];
        foreach ($res as $submission) {
            $submitter = $submission['submitter'];
            $pid = $submission['problem_id'];
            if (isset($updated[$submitter]) && isset($updated[$submitter][$pid])) {
                continue;
            }
            updateBestACSubmissions($submitter, $pid);
            if (!isset($updated[$submitter])) {
                $updated[$submitter] = [];
            }
            $updated[$submitter][$pid] = true;
        }
        
        DB::update([
            "update contests",
            "set", ["status" => 'testing'],
            "where", ["id" => $contest['id']]
        ]);
    }

    public static function announceOfficialResults() {
        // time config
        set_time_limit(0);
        ignore_user_abort(true);

        $contest = self::info();

        $data = queryContestData($contest);
        $update_contests_submissions = $contest['cur_progress'] != CONTEST_FINISHED;
        calcStandings($contest, $data, $score, $standings, $update_contests_submissions);
        if (!isset($contest['extra_config']['unrated'])) {
            $rating_k = isset($contest['extra_config']['rating_k']) ? $contest['extra_config']['rating_k'] : 400;
            $ratings = calcRating($standings, $rating_k);
        } else {
            $ratings = array();
            for ($i = 0; $i < count($standings); $i++) {
                $ratings[$i] = $standings[$i][2][1];
            }
        }

        for ($i = 0; $i < count($standings); $i++) {
            $username = $standings[$i][2][0];
            $userrating = $standings[$i][2][1];
            $change = $ratings[$i] - $userrating;

            $title = 'Rating变化通知';

            if ($contest['cur_progress'] == CONTEST_FINISHED) {
                $title .= '（更正）';
            }

            $content = '<p>'.getUserLink($username, $userrating).' 您好：</p>';
            $content .= '<p class="indent2">';
            if ($change != 0) {
                $chstr = ($change > 0 ? '+' : '').$change;
                $content .= '您在 <a href="/contest/'.$contest['id'].'">'.$contest['name'].'</a> 这场比赛后的Rating变化为<strong style="color:red">'.$chstr.'</strong>，';
            } else {
                $content .= '您在 <a href="/contest/'.$contest['id'].'">'.$contest['name'].'</a> 这场比赛后Rating没有变化。';
            }
            $content .= '当前Rating为 <strong style="color:red">'.$ratings[$i].'</strong>。';
            $content .= '</p>';
            sendSystemMsg($username, $title, $content);

            DB::update([
                "update user_info",
                "set", ["rating" => "{$ratings[$i]}"],
                "where", ["username" => $username]
            ]);
            DB::update([
                "update contests_registrants",
                "set", ["final_rank" => $standings[$i][3]],
                "where", [
                    "contest_id" => $contest['id'],
                    "username" => $username
                ]
            ]);
        }

        if ($contest['cur_progress'] != CONTEST_FINISHED) {
            DB::update([
                "update contests",
                "set", ["status" => 'finished'],
                "where", ["id" => $contest['id']]
            ]);
        }

        UOJRanklist::updateActiveUserList();

        foreach (DB::selectAll([
            "select * from contests",
            "where", [['status', '!=', 'finished']]
        ]) as $info) {
            (new UOJContest($info))->updatePreContestRating();
        }
    }

    public function __construct($info) {
        $this->info = $info;
        $this->completeInfo();
    }

    public function completeInfo() {
        if (isset($this->info['cur_progress'])) {
            return;
        }
        $this->info['start_time_str'] = $this->info['start_time'];
        $this->info['start_time'] = new DateTime($this->info['start_time']);
        $this->info['end_time_str'] = $this->info['end_time'];
        $this->info['end_time'] = new DateTime($this->info['end_time']);
        
        $this->info['extra_config'] = json_decode($this->info['extra_config'], true);
        
        if (!isset($this->info['extra_config']['standings_version'])) {
            $this->info['extra_config']['standings_version'] = 2;
        }
        if (!isset($this->info['extra_config']['basic_rule'])) {
            $this->info['extra_config']['basic_rule'] = 'UOJ-OI';
        }
        if (!isset($this->info['extra_config']['free_registration'])) {
            $this->info['extra_config']['free_registration'] = 1;
        }
        if (!isset($this->info['extra_config']['individual_or_team'])) {
            $this->info['extra_config']['individual_or_team'] = 'individual';
        }
        if (!isset($this->info['extra_config']['bonus'])) {
            $this->info['extra_config']['bonus'] = [];
        }
        if (!isset($this->info['extra_config']['submit_time_limit'])) {
            $this->info['extra_config']['submit_time_limit'] = [];
        }
        if (!isset($this->info['extra_config']['max_n_submissions_per_problem'])) {
            $this->info['extra_config']['max_n_submissions_per_problem'] = -1;
        }
        
        if ($this->info['status'] == 'unfinished') {
            if (UOJTime::$time_now < $this->info['start_time']) {
                $this->info['cur_progress'] = CONTEST_NOT_STARTED;
            } else if (UOJTime::$time_now < $this->info['end_time']) {
                $this->info['cur_progress'] = CONTEST_IN_PROGRESS;
            } else {
                if ($this->info['extra_config']['basic_rule'] == 'UOJ-IOI') {
                    $this->info['cur_progress'] = CONTEST_TESTING;
                } else {
                    $this->info['cur_progress'] = CONTEST_PENDING_FINAL_TEST;
                }
            }
        } else if ($this->info['status'] == 'testing') {
            $this->info['cur_progress'] = CONTEST_TESTING;
        } else if ($this->info['status'] == 'finished') {
            $this->info['cur_progress'] = CONTEST_FINISHED;
        }
        
        $this->info['frozen_time'] = false;
        $this->info['frozen'] = false;
        
        if ($this->info['extra_config']['basic_rule'] == 'UOJ-ACM') {
            $this->info['frozen_time'] = clone $this->info['end_time'];
            
            $frozen_min = min($this->info['last_min'] / 5, 60);
                
            $this->info['frozen_time']->sub(new DateInterval("PT{$frozen_min}M"));
            $this->info['frozen'] = $this->info['cur_progress'] < CONTEST_TESTING && UOJTime::$time_now > $this->info['frozen_time'];
        }
    }

    public function basicRule() {
        return $this->info['extra_config']['basic_rule'];
    }

    public function isRated() {
        return !isset($this->info['extra_config']['unrated']);
    }
    
    public function progress() {
        return $this->info['cur_progress'];
    }
    
    public function maxSubmissionCountPerProblem() {
        return $this->info['extra_config']['max_n_submissions_per_problem'];
    }
    
    public function freeRegistration() {
        return $this->info['extra_config']['free_registration'];
    }

    public function labelForFinalTest() {
        if ($this->basicRule() === 'UOJ-ACM') {
            $label = '揭榜';
        } else {
            $label = '开始最终测试';
        }
        if ($this->progress() >= CONTEST_TESTING) {
            $label = '重新'.$label;
        }
        return $label;
    }

    public function queryJudgeProgress() {
		if ($this->basicRule() == 'UOJ-OI' && $this->progress() < CONTEST_TESTING) {
			$rop = 0;
			$title = UOJLocale::get('contests::contest pending final test');
		} else {
			$total = DB::selectCount([
                "select count(*) from submissions",
                "where", ["contest_id" => $this->info['id']]
            ]);
			$n_judged = DB::selectCount([
                "select count(*) from submissions",
                "where", [
                    "contest_id" => $this->info['id'],
                    "status" => 'Judged'
                ]
            ]);
			$rop = $total == 0 ? 100 : (int)($n_judged / $total * 100);
			
			$title = UOJLocale::get('contests::contest final testing');
			if ($this->basicRule() != 'UOJ-OI' && $n_judged == $total) {
			    $title = UOJLocale::get('contests::contest official results to be announced');
			}
        }
        return [
            'rop' => $rop,
            'title' => $title
        ];
    }

    public function queryResult() {
		$contest_data = queryContestData($this->info);
		calcStandings($this->info, $contest_data, $score, $standings);
        return [
			'standings' => $standings,
			'score' => $score,
			'contest_data' => $contest_data
        ];
    }

    public function managerCanSeeFinalStandingsTab(array $user = null) {
        if ($this->basicRule() == 'UOJ-IOI') {
            return false;
        }
		return $this->progress() < CONTEST_TESTING;
    }

    public function managerCanSeeStandingsUnfrozenTab(array $user = null) {
		return $this->basicRule() == 'UOJ-ACM' && $this->progress() < CONTEST_TESTING;
    }

    public function userCanSeeProblemStatistics($user) {
        return $this->userCanManage($user) || $this->progress() > CONTEST_IN_PROGRESS;
    }

    public function userCanRegister(array $user = null, $cfg = []) {
        $cfg += ['ensure' => false];
        
        if (!$user) {
            $cfg['ensure'] && redirectToLogin();
            return false;
        }
        if (!$this->freeRegistration()) {
            $cfg['ensure'] && $this->redirectToAnnouncementBlog();
            return false;
        }
        if ($this->userCanManage($user) || $this->userHasRegistered($user) || $this->progress() > CONTEST_NOT_STARTED) {
            $cfg['ensure'] && redirectTo('/contests');
            return false;
        }
        if (isTmpUser($user)) {
            $cfg['ensure'] && UOJResponse::message("<h1>临时账号无法报名该比赛</h1><p>换个自己注册的账号试试吧~</p>");
            return false;
        }
        return true;
    }
    
    public function userCanView(array $user = null, $cfg = []) {
        $cfg += [
            'ensure' => false,
            'check-register' => false
        ];
        
        if ($this->userCanManage($user)) {
            return true;
        }
        if ($this->progress() == CONTEST_NOT_STARTED) {
            $cfg['ensure'] && redirectTo($this->getUri('/register'));
            return false;
        } elseif ($this->progress() <= CONTEST_IN_PROGRESS) {
            if ($cfg['check-register']) {
                if ($user && $this->userHasRegistered($user)) {
                    return true;
                }
                $cfg['ensure'] && UOJResponse::message("<h1>比赛正在进行中</h1><p>很遗憾，您尚未报名。比赛结束后再来看吧～</p>");
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    public function userCanParticipateNow(array $user = null) {
        if ($this->userCanManage($user)) {
            return false;
        }
        return $this->progress() == CONTEST_IN_PROGRESS && $user && $this->userHasRegistered($user);
    }
    
    public function registrantNeedToConfirmParticipation(array $user = null) {
        // assert($user has registered)
        $late_time = clone $this->info['start_time'];
        $late_time->add(new DateInterval('PT'.LATE_FOR_CONTEST));
        return $this->isRated() && $this->progress() == CONTEST_IN_PROGRESS && UOJTime::$time_now > $late_time;
    }
    
    public function userCanManage(array $user = null) {
        if (!$user) {
            return false;
	    }
        if (isSuperUser($user)) {
            return true;
        }
        return DB::selectFirst([
            DB::lc(), "select 1 from contests_permissions",
            "where", [
                'username' => $user['username'],
                'contest_id' => $this->info['id']
            ]
        ]) != null;
    }
    
    public function userHasRegistered(array $user = null) {
        if (!$user) {
            return false;
        }
        return DB::selectFirst([
		    DB::lc(), "select 1 from contests_registrants",
		    "where", [
			    'username' => $user['username'],
			    'contest_id' => $this->info['id']
		    ]
	    ]) != null;
    }

    public function defaultProblemJudgeType() {
        if ($this->basicRule() == 'UOJ-OI') {
            return 'sample';
        } else {
            return 'no-details';
        }
    }

    public function getProblemIDs() {
        return array_map(fn($x) => $x['problem_id'], DB::selectAll([
            DB::lc(), "select problem_id from contests_problems",
            "where", ['contest_id' => $this->info['id']],
            "order by problem_id asc"
        ]));
    }
    
    public function hasProblem(UOJProblem $problem) {
        return DB::selectFirst([
		    DB::lc(), "select 1 from contests_problems",
		    "where", [
			    'contest_id' => $this->info['id'],
			    'problem_id' => $problem->info['id']
		    ]
	    ]) != null;
    }

    public function userHasMarkedParticipated(array $user = null) {
        if (!$user) {
            return false;
        }
        return DB::selectExists([
            "select 1 from contests_registrants",
            "where", [
                "username" => $user['username'],
                "contest_id" => $this->info['id'],
                "has_participated" => 1
            ]
        ]);
    }
    
    public function markUserAsParticipated(array $user = null) {
        if (!$user) {
            return false;
        }
        return DB::update([
			"update contests_registrants",
			"set", ["has_participated" => 1],
			"where", [
				"username" => $user['username'],
				"contest_id" => $this->info['id']
			]
		]);
    }

    public function getUri($where = '') {
        return "/contest/{$this->info['id']}{$where}";
    }

    public function redirectToAnnouncementBlog() {
        $url = getContestBlogLink($this->info, '公告');
        if ($url !== null) {
            redirectTo($url);
        } else {
            redirectTo('/contests');
        }
    }

    public function userRegister(array $user = null) {
        if (!$user) {
            return false;
        }
		DB::insert([
            "insert into contests_registrants",
            "(username, user_rating, contest_id, has_participated)",
            "values", DB::tuple([$user['username'], $user['rating'], $this->info['id'], 0])
        ]);
        updateContestPlayerNum($this->info);
        return true;
    }
    public function userUnregister(array $user = null) {
        if (!$user) {
            return false;
        }
        DB::delete([
            "delete from contests_registrants",
            "where", [
                "username" => $user['username'],
                "contest_id" => $this->info['id']
            ]
        ]);
        updateContestPlayerNum($this->info);
        return true;
    }

    public function updatePreContestRating() {
        DB::update([
            "update contests_registrants",
            "set", [
                "user_rating" => DB::rawbracket([
                    "select rating from user_info",
                    "where", ['user_info.username = contests_registrants.username']
                ])
            ],
            "where", [
                "contest_id" => $this->info['id'],
            ]
        ]);
    }
}
