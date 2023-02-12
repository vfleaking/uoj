<?php

// this class depends on getUOJConf from uoj-judger-lib.php sometimes
// be sure to include the lib
// TODO: move getUOJConf into a static class independent of uoj-judger-lib.php

class UOJProblem {
    use UOJDataTrait;
	use UOJArticleTrait;

    protected ?UOJProblemCandidateDataManager $candidate_data_manager = null;

    public static function query($id) {
        if (!isset($id) || !validateUInt($id)) {
            return null;
        }
        $info = DB::selectFirst([
		    "select * from problems",
		    "where", ['id' => $id]
        ]);
        if (!$info) {
            return null;
        }
        return new UOJProblem($info);
    }

    public static function upgradeToContestProblem() {
        return (new UOJContestProblem(self::cur()->info, UOJContest::cur()))->setAsCur()->valid();
    }

    public static function userCanManageSomeProblem(array $user = null) {
        if (!$user) {
            return false;
        }
        return DB::selectFirst([
            DB::lc(), "select 1 from problems_permissions",
            "where", [
                'username' => $user['username']
            ], DB::limit(1)
        ]) != null;
    }

    public function __construct($info) {
        $this->info = $info;
    }

    public function getTitle(array $cfg = []) {
        $cfg += [
            'with' => 'id',
            'simplify' => false
        ];
        $title = $this->info['title'];
        if ($cfg['simplify']) {
            $title = trim($title);
            $title = mb_ereg_replace('^(\[[^\]]*\]|【[^】]*】)', '', $title);
            $title = trim($title);
        }
        if ($cfg['with'] == 'id') {
            return "#{$this->info['id']}. {$title}";
        } else {
            return $title;
        }
    }

    public function getUri($where = '') {
	    return "/problem/{$this->info['id']}{$where}";
    }

    public function getLink(array $cfg = []) {
        return HTML::link($this->getUri(), $this->getTitle($cfg));
    }

    public function getAttachmentUri() {
        return '/download/problem/'.$this->info['id'].'/attachment.zip';
    }

    public function getMainDataUri() {
        return '/download/problem/'.$this->info['id'].'/main.zip';
    }

    public function findInContests() {
        $res = DB::selectAll([
            "select contest_id from contests_problems",
            "where", ['problem_id' => $this->info['id']]
        ]);
        $cps = [];
        foreach ($res as $row) {
            $cp = new UOJContestProblem($this->info, UOJContest::query($row['contest_id']));
            if ($cp->valid()) {
                $cps[] = $cp;
            }
        }
        return $cps;
    }

    public function userCanClickZan(array $user = null) {
        if ($this->userCanView($user)) {
            return true;
        }
        foreach ($this->findInContests() as $cp) {
            if ($cp->userCanClickZan($user)) {
                return true;
            }
        }
        return false;
    }
    
    public function getZanBlock() {
        return ClickZans::getBlock('P', $this->info['id'], $this->info['zan']);
    }

    public function getSubmissionRequirement() {
        return json_decode($this->info['submission_requirement'], true);
    }
    public function getExtraConfig($key = null) {
        $extra_config = json_decode($this->info['extra_config'], true);
        mergeConfig($extra_config, [
            'view_content_type' => 'ALL',
            'view_all_details_type' => 'ALL',
            'view_details_type' => 'ALL',
            'need_to_review_hack' => false,
            'add_hack_as' => 'ex_test'
        ]);
        
        return $key === null ? $extra_config : $extra_config[$key];
    }
    public function getCustomTestRequirement() {
        $extra_config = json_decode($this->info['extra_config'], true);
        if (isset($extra_config['custom_test_requirement'])) {
            return $extra_config['custom_test_requirement'];
        } else {
            $answer = [
                'name' => 'answer',
                'type' => 'source code',
                'file_name' => 'answer.code'
            ];
            foreach ($this->getSubmissionRequirement() as $req) {
                if ($req['name'] == 'answer' && $req['type'] == 'source code' && isset($req['languages'])) {
                    $answer['languages'] = $req['languages'];
                }
            }
            return [
                $answer, [
                    'name' => 'input',
                    'type' => 'text',
                    'file_name' => 'input.txt'
                ]
            ];
        }
    }

    public function userCanView(array $user = null, array $cfg = []) {
        $cfg += ['ensure' => false];
        if ($this->info['is_hidden'] && !$this->userCanManage($user)) {
            $cfg['ensure'] && UOJResponse::page404();
            return false;
        }
        return true;
    }

    /**
     * Get a SQL cause to determine whether a user can view a problem
     * Need to be consistent with the member function userCanView
     */
    public static function sqlForUserCanView(array $user = null) {
        if (isSuperUser($user)) {
            return "(1)";
        } elseif (UOJProblem::userCanManageSomeProblem($user)) {
            return DB::lor([
                "problems.is_hidden" => false,
                DB::land([
                    "problems.is_hidden" => true, [
                        "problems.id", "in", DB::rawbracket([
                            "select problem_id from problems_permissions",
                            "where", ["username" => $user['username']]
                        ])
                    ]
                ])
            ]);
        } else {
            return "(problems.is_hidden = false)";
        }
    }

    public function userCanUploadSubmissionViaZip(array $user = null) {
	    foreach ($this->getSubmissionRequirement() as $req) {
		    if ($req['type'] == 'source code') {
                return false;
            }
        }
        return true;
    }

    public function userCanDownloadAttachments(array $user = null) {
        if ($this->userCanView($user)) {
            return true;
        }
        foreach ($this->findInContests() as $cp) {
            if ($cp->userCanDownloadAttachments($user)) {
                return true;
            }
        }
        return false;
    }

    public function userCanManage(array $user = null) {
        if (!$user) {
            return false;
        }
        if (isSuperUser($user)) {
            return true;
        }
        return DB::selectFirst([
            DB::lc(), "select 1 from problems_permissions",
            "where", [
                'username' => $user['username'],
                'problem_id' => $this->info['id']
            ]
        ]) != null;
    }

    public function preHackCheck(array $user = null) {
        return $this->info['hackable'] && (!$user || $this->userCanView($user));
    }

    public function needToReviewHack() {
        return $this->getExtraConfig('need_to_review_hack');
    }

    public function userHasAC(array $user = null) {
        if (!$user) {
            return false;
        }
        return DB::selectFirst([
            DB::lc(), "select 1 from best_ac_submissions",
            "where", [
                'submitter' => $user['username'],
                'problem_id' => $this->info['id']
            ]
        ]) != null;
    }

    public function preSubmitCheck() {
        return true;
    }

    public function additionalSubmissionComponentsCannotBeSeenByUser(array $user = null, UOJSubmission $submission) {
        return [];
    }

    public function getDataFolderPath() {
        return "/var/uoj_data/{$this->info['id']}";
    }

    public function getDataZipPath() {
        return "/var/uoj_data/{$this->info['id']}.zip";
    }

    public function getCandidateDataPath() {
		return "/var/svn/problem/{$this->info['id']}/cur/{$this->info['id']}/1";
    }

    public function getCandidateDataSVNPath() {
		return "/var/svn/problem/{$this->info['id']}";
    }

    public function getSVNRepoURL() {
        return "svn://".UOJContext::httpHost()."/problem/{$this->info['id']}";
    }

    public function getCandidateDataManager() {
        if (!$this->candidate_data_manager) {
            $this->candidate_data_manager = new UOJProblemCandidateDataManager($this);
        }
        return $this->candidate_data_manager;
    }

    public function getCandidateDataURI() {
		return "/problem/{$this->info['id']}/data/candidate";
    }

    public function getDataFilePath($name = '') {
        return "zip://{$this->getDataZipPath()}#{$this->info['id']}/$name";
    }

    public function getCandidateDataFilePath($name = '', $cfg = []) {
        $cfg += [
            'security_check' => false
        ];
        $path = $this->getCandidateDataPath()."/$name";
        if ($cfg['security_check']) {
            $path = realpath($path);
            $data_path = realpath($this->getCandidateDataPath());
            if ($path === false || $data_path === false || !strStartWith($path, "{$data_path}/")) {
                return false;
            }
        }
        return $path;
    }

    public function getProblemConfArray(string $where = 'data') {
        if ($where === 'data') {
            return getUOJConf($this->getDataFilePath('problem.conf'));
        } elseif ($where === 'candidate') {
            return getUOJConf($this->getCandidateDataFilePath('problem.conf'));
        } else {
            return null;
        }
    }

    public function getProblemConf(string $where = 'data') {
        if ($where === 'data') {
            return UOJProblemConf::getFromFile($this->getDataFilePath('problem.conf'));
        } elseif ($where === 'candidate') {
            return UOJProblemConf::getFromFile($this->getCandidateDataFilePath('problem.conf'));
        } else {
            return null;
        }
    }

    public function getNonTraditionalJudgeType() {
        $conf = $this->getProblemConf();
        if (!($conf instanceof UOJProblemConf)) {
            return false;
        }
        return $conf->getNonTraditionalJudgeType();
    }

    public function syncData($user = null) {
	    return (new UOJProblemDataSynchronizer($this, $user))->sync();
    }

    public function addHackPoint($uploaded_input_file, $uploaded_output_file, $reason = null, $user = null) {
        if ($reason === null) {
            if (UOJHack::cur()) {
                $reason = [
                    'rejudge' => '自动重测本题所有获得100分的提交记录',
                    'hack_url' => HTML::url(UOJHack::cur()->getUri())
                ];
            } else {
                $reason = [];
            }
        }
        return (new UOJProblemDataSynchronizer($this, $user))->addHackPoint($uploaded_input_file, $uploaded_output_file, $reason);
    }

    public function uploadDataViaZipFile($new_data_zip) {
        return (new UOJProblemDataSynchronizer($this))->upload($new_data_zip);
    }

    public function updateCandidateProblemConf($new_problem_conf) {
        return (new UOJProblemDataSynchronizer($this))->updateProblemConf($new_problem_conf);
    }
}

UOJProblem::$table_for_content = 'problems_contents';
UOJProblem::$key_for_content = 'id';
UOJProblem::$fields_for_content = ['*'];
UOJProblem::$table_for_tags = 'problems_tags';
UOJProblem::$key_for_tags = 'problem_id';
