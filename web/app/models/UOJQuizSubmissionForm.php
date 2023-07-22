<?php

class UOJQuizSubmissionForm extends UOJForm {

    public UOJSubmissionArchive $submission_archive;

    private bool $in_question_list = false;

    public function __construct($form_name, $quiz_cfg, $zip_file_name_gen = null, $handle = null) {
        parent::__construct($form_name);

        $this->error_on_incomplete_form = false;

        $all_qs = [];
        foreach ($quiz_cfg as $q) {
            if ($q['type'] == 'single') {
                if (!$this->in_question_list) {
                    $this->enterQuestionList();
                    $this->in_question_list = true;
                }
                $q['id'] = count($all_qs) + 1;
                $all_qs[] = $q;
                $this->addSingleChoiceQuestion($q);
            } else if ($q['type'] == 'multiple') {
                if (!$this->in_question_list) {
                    $this->enterQuestionList();
                    $this->in_question_list = true;
                }
                $q['id'] = count($all_qs) + 1;
                $all_qs[] = $q;
                $this->addMultipleChoiceQuestion($q);
            } else {
                if ($this->in_question_list) {
                    $this->leaveQuestionList();
                    $this->in_question_list = false;
                }
                $this->appendHTML($q['html']);
            }
        }
        if ($this->in_question_list) {
            $this->leaveQuestionList();
        }

        if (empty($zip_file_name_gen) || empty($handle)) {
            return;
        }

        $this->handle = function(&$vdata) use($all_qs, $zip_file_name_gen, $handle) {
            Auth::check() || redirectToLogin();

            try {
                $this->submission_archive = UOJSubmissionArchive::create($zip_file_name_gen);
            } catch (UOJException $e) {
                UOJResponse::message($e->getMessage());
            }

            $req = [
                'name' => 'output1',
                'type' => 'text',
                'file_name' => 'output1.txt'
            ];

            $answer = '';
            foreach ($all_qs as $q) {
                $answer .= $vdata[$this->getQuestionName($q['id'])]."\n";
            }

            $this->submission_archive->addFromString($req, $answer);

            $this->submission_archive->close();
            
            $handle($this->submission_archive);
        };
    }

    public function getQuestionName($qid) {
        return "{$this->form_name}_Q{$qid}";
    }

    private function enterQuestionList() {
        $this->appendHTML(<<<EOD
        <div class="list-group uoj-quiz">
        EOD);
    }

    private function leaveQuestionList() {
        $this->appendHTML(<<<EOD
        </div>
        EOD);
    }

    public function addSingleChoiceQuestion($q) {
        $qid = $q['id'];

        $html = '<div class="list-group-item uoj-quiz-q">';
        $html .= '<div class="uoj-quiz-q-id">'."{$qid}.".'</div>';
        $html .= '<div class="uoj-quiz-q-content">';
        $html .= '<div class="uoj-quiz-q-prompt">'.$q['html'].'</div>';
        $choices = [];
        foreach ($q as $ckey => $cval) {
            if ($ckey == 'html' || strlen($ckey) != 1) {
                continue;
            }
            $choices[$ckey] = true;
            $html .= '<div class="radio uoj-quiz-q-choices">';
            $html .= '<label>';
            $html .= HTML::radio($this->getQuestionName($qid), $ckey);
            $html .= '<div class="uoj-quiz-q-ckey">'."$ckey.".'</div>';
            $html .= '<div class="uoj-quiz-q-cval">'.$cval.'</div>';
            $html .= '</label>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';

		$this->add(
            $this->getQuestionName($qid),
            $html,
			function($ckey) use ($choices) {
                if ($ckey === null) {
                    return ['error' => '', 'store' => '/'];
                }
                if (!is_string($ckey) || !isset($choices[$ckey])) {
                    return ['error' => "无效选项"];
                }
                return ['error' => '', 'store' => $ckey];
			},
			null
        );
    }

    public function addMultipleChoiceQuestion($q) {
        $qid = $q['id'];

        $html = '<div class="list-group-item uoj-quiz-q">';
        $html .= '<div class="uoj-quiz-q-id">'."{$qid}.".'</div>';
        $html .= '<div class="uoj-quiz-q-content">';
        $html .= '<div class="uoj-quiz-q-prompt">'.$q['html'].'</div>';
        $choices = [];
        foreach ($q as $ckey => $cval) {
            if ($ckey == 'html' || strlen($ckey) != 1) {
                continue;
            }
            $choices[$ckey] = true;
            $html .= '<div class="checkbox uoj-quiz-q-choices">';
            $html .= '<label>';
            $html .= HTML::checkbox_in_array($this->getQuestionName($qid), $ckey);
            $html .= '<div class="uoj-quiz-q-ckey">'."$ckey.".'</div>';
            $html .= '<div class="uoj-quiz-q-cval">'.$cval.'</div>';
            $html .= '</label>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';

		$this->add(
            $this->getQuestionName($qid),
            $html,
			function($ckeys) use ($choices) {
                if ($ckeys === null) {
                    return ['error' => '', 'store' => '/'];
                }
                $wuxiao = "无效选项";
                if (!is_array($ckeys) || !array_is_list($ckeys)) {
                    return ['error' => $wuxiao];
                }
                $occured = [];
                $answer = '';
                sort($ckeys);
                foreach ($ckeys as $ckey) {
                    if (!is_string($ckey) || !isset($choices[$ckey])) {
                        return ['error' => $wuxiao];
                    }
                    if (isset($occured[$ckey])) {
                        return ['error' => $wuxiao];
                    }
                    $occured[$ckey] = true;
                    $answer .= $ckey;
                }
                return ['error' => '', 'store' => $answer];
			},
			null
        );
    }

    protected function printFormJS() {
		echo <<<EOD
        <script type="text/javascript">
            $(document).ready(function() {
                quiz_problem_form_init("{$this->form_name}")
            });
        </script>
        EOD;
    }
}
