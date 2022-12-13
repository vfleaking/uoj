<?php

class UOJProblemConfigure {
    public UOJProblem $problem;
    public array $problem_conf;
    public string $href;

    public array $conf_keys;
    public UOJForm $simple_form;

    public static $supported_checkers = [
        'ownchk' => '自定义校验器',
        'ncmp' => 'ncmp: 单行或多行整数序列',
        'wcmp' => 'wcmp: 单行或多行字符串序列',
        'fcmp' => 'fcmp: 单行或多行数据（不忽略行末空格，但忽略文末回车）',
        'bcmp' => 'bcmp: 逐字节比较',
        'yesno' => 'yesno: 单个YES或NO（不区分大小写）',
        'rcmp4' => 'rcmp4: 浮点数序列，绝对或相对误差在 1e-4 以内则视为答案正确',
        'rcmp6' => 'rcmp6: 浮点数序列，绝对或相对误差在 1e-6 以内则视为答案正确',
        'rcmp9' => 'rcmp9: 浮点数序列，绝对或相对误差在 1e-9 以内则视为答案正确',
    ];

    public function __construct(UOJProblem $problem) {
        $this->problem = $problem;
        $problem_conf = $this->problem->getProblemConfArray('candidate');
        if (!is_array($problem_conf)) {
            $problem_conf = [];
        }
        $this->problem_conf = $problem_conf;

        $this->href = "/problem/{$this->problem->info['id']}/manage/data";

        $this->simple_form = new UOJForm('simple');
        $this->simple_form->control_label_config['class'] = 'col-sm-3 col-sm-push-2';
        $this->simple_form->input_config['class'] = 'col-sm-3 col-sm-push-2';
        $this->addSelect($this->simple_form, 'use_builtin_judger', ['on' => '默认', 'off' => '自定义judger'], '测评逻辑', 'off');
        $this->addSelect($this->simple_form, 'use_builtin_checker', self::$supported_checkers, '比对函数', 'ownchk');
        $this->addNumberInput($this->simple_form, 'n_tests', '数据点个数', 10);
        $this->addNumberInput($this->simple_form, 'n_ex_tests', '额外数据点个数', 0);
        $this->addNumberInput($this->simple_form, 'n_sample_tests', '样例数据点个数', 0);
        $this->addTextInput($this->simple_form, 'input_pre', '输入文件名称', '');
        $this->addTextInput($this->simple_form, 'input_suf', '输入文件后缀', '');
        $this->addTextInput($this->simple_form, 'output_pre', '输出文件名称', '');
        $this->addTextInput($this->simple_form, 'output_suf', '输出文件后缀', '');
        $this->addTimeLimitInput($this->simple_form, 'time_limit', '时间限制（单位为秒，至多三位小数）', 1);
        $this->addNumberInput($this->simple_form, 'memory_limit', '内存限制（MB）', 256);
        $this->addNumberInput($this->simple_form, 'output_limit', '输出长度限制（MB）', 64);
        $this->simple_form->handle = fn(&$vdata) => $this->onUpload($vdata);
        $this->simple_form->submit_button_config['class_str'] = 'btn btn-primary';
        $this->simple_form->succ_href = $this->href;
        $this->simple_form->back_href = $this->href;
    }

    public function addSelect(UOJForm $form, $key, $options, $label, $default_val = '') {
        $this->conf_keys[$key] = true;
        $form->addSelect(
            $key, $options, $label,
            getUOJConfVal($this->problem_conf, $key, $default_val)
        );
    }

    public function addNumberInput(UOJForm $form, $key, $label, $default_val = '') {
        $this->conf_keys[$key] = true;
        $form->addInput(
            $key, 'number', $label,
            getUOJConfVal($this->problem_conf, $key, $default_val),
            function($x) {
                return validateInt($x) ? '' : '必须为一个整数';
            }, null
        );
    }

    public function addTimeLimitInput(UOJForm $form, $key, $label, $default_val = '') {
        $this->conf_keys[$key] = true;
        $form->addInput(
            $key, ['type' => 'number', 'step' => 0.001], $label,
            getUOJConfVal($this->problem_conf, $key, $default_val),
            function($x) {
                if (!validateUFloat($x)) {
                    return '必须为整数或小数，且值大于等于零';
                } elseif (round($x * 1000) != $x * 1000) {
                    return '至多包含三位小数';
                } else {
                    return '';
                }
            }, null
        );
    }

    public function addTextInput(UOJForm $form, $key, $label, $default_val = '') {
        $this->conf_keys[$key] = true;
        $form->addInput(
            $key, 'text', $label,
            getUOJConfVal($this->problem_conf, $key, $default_val),
            function($str) {
                return ctype_graph($str) ? '' : '必须仅包含除空格以外的可见字符';
            }, null
        );
    }

    public function runAtServer() {
        $this->simple_form->runAtServer();
    }

    public function onUpload(array &$vdata) {
        $conf = $this->problem_conf;
        foreach (array_keys($this->conf_keys) as $key) {
            $val = UOJRequest::post($key);
            if ($key === 'use_builtin_judger') {
                if ($val === 'off') {
                    unset($conf[$key]);
                } else {
                    $conf[$key] = $val;
                }
            } elseif ($key === 'use_builtin_checker') {
                if ($val === 'ownchk') {
                    unset($conf[$key]);
                } else {
                    $conf[$key] = $val;
                }
            } else {
                if ($val !== '') {
                    $conf[$key] = $val;
                }
            }
        }

        $err = svnUpdateProblemConf($this->problem->info, $conf);
        if ($err) {
            UOJResponse::message('<div>'.$err.'</div><a href="'.$this->href.'">返回</a>');
        }
    }

    public function printHTML() {
        $this->simple_form->printHTML();
    }
}