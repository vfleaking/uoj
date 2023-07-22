<?php

requirePHPLib('judger');
requirePHPLib('svn');

UOJProblem::init(UOJRequest::get('id'));

if (UOJProblem::cur()) {
    UOJProblem::cur()->userCanManage(Auth::user()) || UOJResponse::page403();

    $page_title = HTML::stripTags(UOJProblem::info('title')) . ' - 重置数据';
    $page_h1 = '#'.UOJProblem::info('id').' : '.UOJProblem::info('title').' 重置数据';
} else {
    // only super user can add new problems
    isSuperUser(Auth::user()) || UOJResponse::page404();
    $page_title = HTML::stripTags('添加新题');
    $page_h1 = '添加新题';
}

$init_problem_form = new UOJForm('init_problem');
$init_problem_form->control_label_config['class'] = 'col-sm-3 col-sm-push-2';
$init_problem_form->input_config['class'] = 'col-sm-3 col-sm-push-2';

$init_problem_form->addInput(
    'title', 'text', '题目名称',
    UOJProblem::cur() ? UOJProblem::info('title') : 'New Problem',
    'validateNothing', null
);

$init_problem_form->addSelect('type', [
    'normal' => '普通型',
    'submit-answer' => '提交答案型',
    'interaction' => '简单通信型',
    'custom-judger' => '自定义测评型',
    'quiz' => '在线测验型'
], '题目基本类型', 'normal');

$init_problem_form->handle = function() {
    $type = UOJRequest::post('type');
    $title = UOJRequest::post('title');

    $extra_config = [];
    if (!in_array($type, ['normal', 'interaction'])) {
        $extra_config['custom_test_requirement'] = false;
    }
    if ($type == 'quiz') {
        $extra_config['presentation_mode'] = 'quiz';
    }

    if (!UOJProblem::cur()) {
        $info = [
            'title' => $title,
            'is_hidden' => '1',
            'submission_requirement' => '{}',
            'extra_config' => json_encode($extra_config),
            'hackable' => '0'
        ];
        DB::insert([
            "insert into problems",
            DB::bracketed_fields(array_keys($info)),
            "values", DB::tuple(array_values($info))
        ]);
        $id = DB::insert_id();
        $info['id'] = "$id";
        DB::insert([
            "insert into problems_contents",
            "(id, statement, statement_md)",
            "values", DB::tuple([$id, "", ""])
        ]);
        svnNewProblem($id);

        $problem = new UOJProblem($info);
    } else {
        $id = UOJProblem::info('id');

        $problem = UOJProblem::cur();

        $new_values = [
            "title" => $title,
            "submission_requirement" => '{}',
            "extra_config" => json_encode($extra_config),
            "hackable" => '0'
        ];
        foreach ($new_values as $key => $val) {
            $problem->info[$key] = $val;
        }

        DB::update([
            "update problems",
            "set", $new_values,
            "where", ["id" => $id]
        ]);
        svnClearProblemData($problem->info);
    }

    $data = [];

    if ($type == 'normal') {
        $data['problem.conf'] = [
            'use_builtin_judger' => 'on',
            'use_builtin_checker' => 'ncmp',
            'n_tests' => 10,
            'n_ex_tests' => 1,
            'n_sample_tests' => 1
        ];
    } elseif ($type == 'submit-answer') {
        $data['problem.conf'] = [
            'use_builtin_judger' => 'on',
            'submit_answer' => 'on',
            'use_builtin_checker' => 'ncmp',
            'n_tests' => 10,
        ];
    } elseif ($type == 'interaction') {
        $data['problem.conf'] = [
            'use_builtin_judger' => 'on',
            'interaction_mode' => 'on',
            'use_builtin_checker' => 'ncmp',
            'n_tests' => 10,
            'n_ex_tests' => 1,
            'n_sample_tests' => 1
        ];
    } elseif ($type == 'custom-judger') {
        $data['problem.conf'] = [
            'n_tests' => 10,
        ];
    } elseif ($type == 'quiz') {
        $data['problem.conf'] = [
            'use_builtin_judger' => 'on',
            'submit_answer' => 'on',
            'use_builtin_checker' => 'fcmp',
            'n_tests' => 1,
            'show_in' => 'off'
        ];
        $data['input1.txt'] = '';
        $data['output1.txt'] = '';
    }

    $err = $problem->updateCandidateDataFromArray($data);
    if ($err) {
        UOJResponse::message($err);
    }

    redirectTo($problem->getUri('/manage/data'));
};

$init_problem_form->runAtServer();

?>

<?php echoUOJPageHeader($page_title) ?>
<h1 class="page-header text-center"><?= $page_h1 ?></h1>

<?php $init_problem_form->printHTML() ?>

<?php echoUOJPageFooter() ?>
