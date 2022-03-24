<?php

UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJProblem::cur()->userCanManage(Auth::user()) || UOJResponse::page404();

is_string(UOJRequest::get('path')) || UOJResponse::page404();

switch (UOJRequest::get('problem_data_branch')) {
    case 'candidate':
        $filepath = UOJProblem::cur()->getCandidateDataFilePath(UOJRequest::get('path'), ['security_check' => true]);
        if ($filepath === false) {
            UOJResponse::page404();
        }
        UOJResponse::xsendfile($filepath);
    case 'main':
        // TBD
    default:
        UOJResponse::page404();
}
