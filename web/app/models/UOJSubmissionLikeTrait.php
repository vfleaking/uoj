<?php

trait UOJSubmissionLikeTrait {
    use UOJDataTrait;

    /**
     * @var UOJProblem|UOJContestProblem
     */
    public $problem = null;

    public static function getAndRememberSubmissionLanguage(array $content) {
		$language = '/';
		foreach ($content['config'] as $row) {
			if (strEndWith($row[0], '_language')) {
				$language = $row[1];
				break;
			}
		}
		if ($language != '/') {
			Cookie::set('uoj_preferred_language', $language, time() + 60 * 60 * 24 * 365, '/');
        }
        return $language;
    }

    public function setProblem(array $cfg = []) {
        $cfg += ['problem' => 'auto'];
        $problem = $cfg['problem'] === 'auto' ? UOJProblem::query($this->info['problem_id']) : $cfg['problem'];
        if (!($problem instanceof UOJProblem && $problem->info['id'] == $this->info['problem_id'])) {
            return false;
        }
        if (isset($this->info['contest_id'])) {
            if (!($problem instanceof UOJContestProblem && $problem->contest->info['id'] == $this->info['contest_id'])) {
                $problem = new UOJContestProblem($problem->info, UOJContest::query($this->info['contest_id']));
                if (!$problem->valid()) {
                    return false;
                }
            }
        } else {
            if ($problem instanceof UOJContestProblem) {
                $problem = new UOJProblem($problem->info);
            }
        }
        $this->problem = $problem;
        return true;
    }

    public function userIsSubmitter(array $user = null) {
        return $user && $this->info['submitter'] === $user['username'];
    }

    public function userCanView(array $user = null, array $cfg = []) {
        $cfg += ['ensure' => false];
        if (!$this->info['is_hidden']) {
            return true;
        } elseif ($this->userCanManageProblemOrContest($user)) {
            return true;
        } else {
            $cfg['ensure'] && UOJResponse::page404();
            return false;
        }
    }

    public function userCanManageProblemOrContest(array $user = null) {
        if ($this->problem->userCanManage($user)) {
            return true;
        } elseif ($this->problem instanceof UOJContestProblem && $this->problem->contest->userCanManage($user)) {
            return true;
        } else {
            return false;
        }
    }

    public function userCanDelete(array $user = null) {
        return isSuperUser($user);
    }

    public function publicStatus() {
        return explode(', ', $this->info['status'])[0];
    }

    public function isWaiting() {
        $status = $this->publicStatus();
        return $status === 'Waiting' || $status === 'Waiting Rejudge';
    }

    public function hasJudged() {
        return $this->publicStatus() === 'Judged';
    }

    public function userPermissionCodeCheck(array $user = null, $perm_code) {
        switch ($perm_code) {
            case 'ALL':
                return true;
            case 'ALL_AFTER_AC':
                return $this->problem->userHasAC($user);
            case 'SELF':
                return $this->userIsSubmitter($user);
            case 'NONE':
                return false;
            default:
                return null;
        }
    }

    public function viewerCanSeeStatusDetailsHTML(array $user = null) {
        return $this->userIsSubmitter($user) && !$this->hasJudged();
    }
    public function getStatusDetailsHTML() {
        $status = $this->publicStatus();
        $status_details = $this->info['status_details'];

        $html = '<td colspan="233" style="vertical-align: middle">';
        
        $fly = '<img src="//img.uoj.ac/utility/bear-flying.gif" alt="小熊像超人一样飞" class="img-rounded" />';
        $think = '<img src="//img.uoj.ac/utility/bear-thinking.gif" alt="小熊像在思考" class="img-rounded" />';
        
        if ($status == 'Judged') {
            $status_text = '<strong>Judged!</strong>';
            $status_img = $fly;
        } else {
            if ($status_details !== '') {
                $status_img = $fly;
                $status_text = HTML::escape($status_details);
            } else  {
                $status_img = $think;
                $status_text = $status;
            }
        }
        $html .= '<div class="uoj-status-details-img-div">' . $status_img . '</div>';
        $html .= '<div class="uoj-status-details-text-div">' . $status_text . '</div>';

        $html .= '</td>';
        return $html;
    }

    public function getUri() {
        return $this->info['id'];
    }
    public function getLink() {
        return '<a href="'.$this->getUri().'">#'.$this->info['id'].'</a></td>';
    }

    public function getResult($key = null) {
        if (!isset($this->info['result'])) {
            return null;
        }
        $result = json_decode($this->info['result'], true);
        if ($key === null) {
            return $result;
        }
        return isset($result[$key]) ? $result[$key] : null;
    }

    public function getContent($key = null) {
        if (!isset($this->info['content'])) {
            return null;
        }
        $content = json_decode($this->info['content'], true);
        if ($key === null) {
            return $content;
        }
        return isset($content[$key]) ? $content[$key] : null;
    }

    public function echoContent() {
        $content = $this->getContent();
        if (!$content) {
            return false;
        }

        $zip_file = new ZipArchive();
        if ($zip_file->open(UOJContext::storagePath().$content['file_name'], ZipArchive::RDONLY) !== true) {
            echo <<<EOD
            <div class="panel panel-danger">
                <div class="panel-heading">
                    <h4 class="panel-title">提交内容</h4>
                </div>
                <div class="panel-body">
                    木有
                </div>
            </div>
            EOD;
            return false;
        }
        
        $config = [];
        foreach ($content['config'] as $val) {
            $config[$val[0]] = $val[1];
        }
        
        foreach ($this->problem->getSubmissionRequirement() as $req) {
            if ($req['type'] == "source code") {
                $file_content = $zip_file->getFromName("{$req['name']}.code");
                if ($file_content === false) {
                    $file_content = '';
                }

                if (isset($config["{$req['name']}_language"])) {
                    $file_language = $config["{$req['name']}_language"];
                } else {
                    $file_language = '?';
                }

                $file_content = uojTextEncode($file_content, array('allow_CR' => true, 'html_escape' => true));
                $footer_text = UOJLocale::get('problems::source code').': ';
                $footer_text .= UOJLang::getLanguageDisplayName($file_language);
                $sh_class = UOJLang::getLanguagesCSSClass($file_language);
                echo <<<EOD
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h4 class="panel-title">{$req['name']}</h4>
                    </div>
                    <div class="panel-body">
                        <pre><code class="$sh_class">{$file_content}\n</code></pre>
                    </div>
                    <div class="panel-footer">$footer_text</div>
                </div>
                EOD;
            }
            else if ($req['type'] == "text") {
                $file_content = $zip_file->getFromName("{$req['file_name']}", 504);
                if ($file_content === false) {
                    $file_content = '';
                }

                $file_content = strOmit($file_content, 500);
                $file_content = uojTextEncode($file_content, array('allow_CR' => true, 'html_escape' => true));
                $footer_text = UOJLocale::get('problems::text file');
                echo <<<EOD
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h4 class="panel-title">{$req['file_name']}</h4>
                    </div>
                    <div class="panel-body">
                        <pre>\n{$file_content}\n</pre>
                    </div>
                    <div class="panel-footer">$footer_text</div>
                </div>
                EOD;
            }
        }
        $zip_file->close();

        return true;
    }


    protected function getStatusBarTDBase($name, array $cfg) {
        switch ($name) {
            case 'id':
                return $this->getLink();
            case 'problem':
                if ($this->problem) {
                    return $this->problem->getLink(isset($cfg['problem_title']) ? $cfg['problem_title'] : []);
                } else {
                    return '<span class="text-danger">?</span>';
                }
            case 'submitter':
            case 'owner':
            case 'hacker':
                return getUserLink($this->info[$name]);
            case 'used_time':
                if ($cfg['show_actual_score']) {
                    return $this->info['used_time'].'ms';
                } else {
                    return '/';
                }
            case 'used_memory':
                if ($cfg['show_actual_score']) {
                    return $this->info['used_memory'].'kb';
                } else {
                    return '/';
                }
            case 'tot_size':
                if ($this->info['tot_size'] < 1024) {
                    return $this->info['tot_size'] . 'b';
                } else {
                    return sprintf("%.1f", $this->info['tot_size'] / 1024) . 'kb';
                }
            case 'submit_time':
            case 'judge_time':
                return '<small>'.$this->info[$name].'</small>';
            default:
                return '?';
        }
    }
}