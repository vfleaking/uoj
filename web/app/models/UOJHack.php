<?php

class UOJHack {
    use UOJSubmissionLikeTrait;

    /**
     * @var UOJSubmission
     */
    public $submission = null;

    public static function query($id) {
        if (!isset($id) || !validateUInt($id)) {
            return null;
        }
        $info = DB::selectFirst([
            "select * from hacks",
            "where", ['id' => $id]
        ]);
        if (!$info) {
            return null;
        }
        return new UOJHack($info);
    }

    public function __construct($info) {
        $this->info = $info;
    }

    public function setSubmission(array $cfg = []) {
        $cfg += ['submission' => 'auto'];
        $submission = $cfg['submission'] === 'auto' ? UOJSubmission::query($this->info['submission_id']) : $cfg['submission'];
        if (!($submission instanceof UOJSubmission && $submission->info['id'] == $this->info['submission_id'])) {
            return false;
        }
        $this->submission = $submission;
        return true;
    }

    public function userIsSubmitter(array $user = null) {
        return $user && $this->info['owner'] === $user['username'];
    }

    public function getUri() {
        return "/hack/{$this->info['id']}";
    }

    public function viewerCanSeeComponents(array $user = null) {
        // assert($this->userCanView($user));
        $pec = $this->problem->getExtraConfig();

        $perm = ['manager_view' => $this->userCanManageProblemOrContest($user)];
        if ($perm['manager_view']) {
            $user = UOJUser::query($this->info['owner']);
        }

        if ($this->submission && $this->submission->info['hide_score_to_others']) {
            $perm['score'] = $this->userIsSubmitter($user);
        } else {
            $perm['score'] = true;
        }

        $perm['content'] = $this->userPermissionCodeCheck($user, $pec['view_content_type']);
        $perm['high_level_details'] = $this->userPermissionCodeCheck($user, $pec['view_all_details_type']);
        $perm['low_level_details'] = $perm['high_level_details'] && $this->userPermissionCodeCheck($user, $pec['view_details_type']);
        if ($this->submission) {
            foreach ($this->problem->additionalSubmissionComponentsCannotBeSeenByUser($user, $this->submission) as $com) {
                $perm[$com] = false;
            }
        }
        return $perm;
    }

    public function userCanReview(array $user = null) {
        if (!$this->info['success']) {
            return false;
        }
        if ($this->info['status'] !== 'Judged, WaitingM') {
            return false;
        }
        return $this->problem->userCanManage($user);
    }

    public function getStatusBarTD($name, array $cfg) {
        switch ($name) {
            case 'submission':
                if ($this->submission) {
                    return $this->submission->getLink();
                } else {
                    return '<span class="text-danger">?</span>';
                }
            case 'result':
                if ($this->hasJudged()) {
                    if ($this->info['success']) {
                        return '<a href="/hack/'.$this->info['id'].'" class="uoj-status" data-success="1"><strong>Success!</strong></a>';
                    } else {
                        return '<a href="/hack/'.$this->info['id'].'" class="uoj-status" data-success="0"><strong>Failed.</strong></a>';
                    }
                } else {
                    return '<a href="/hack/'.$this->info['id'].'" class="small">'.$this->publicStatus().'</a>';
                }
            default:
                return $this->getStatusBarTDBase($name, $cfg);
        }
    }

    public function echoStatusTableRow(array $cfg, array $viewer = null) {
        echo '<tr>';
        $cols = ['id', 'submission', 'problem', 'hacker', 'owner', 'result', 'submit_time', 'judge_time'];
        foreach ($cols as $name) {
            if (!isset($cfg["{$name}_hidden"])) {
                echo '<td>';
                echo $this->getStatusBarTD($name, $cfg);
                echo '</td>';
            }
        }
        echo '</tr>';
    }
}