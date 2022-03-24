<?php

UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJProblem::cur()->userCanManage(Auth::user()) || UOJResponse::page403();

define('APP_TITLE', UOJProblem::cur()->getTitle(['with' => false]).' - 浏览SVN仓库');
define('FM_EMBED', true);
define('FM_DISABLE_COLS', true);
define('FM_DATETIME_FORMAT', UOJTime::FORMAT);
define('FM_ROOT_PATH', UOJProblem::cur()->getCandidateDataPath());
define('FM_ROOT_URL', UOJProblem::cur()->getCandidateDataURI());

if (!is_dir(FM_ROOT_PATH)) {
    UOJProblem::cur()->getCandidateDataManager()->svnInit() || UOJResponse::page503('失败：可能有别人也在改数据，请稍后重试');
}

$fm_hooks = [
    'pre_update' => function() {
        return UOJProblem::cur()->getCandidateDataManager()->start_update() || UOJResponse::page503('失败：可能有别人也在改数据，请稍后重试');
    },
    'post_update' => function() {
        return UOJProblem::cur()->getCandidateDataManager()->end_update() || UOJResponse::page503('SVN更新失败：再试试或联系管理员看看？');
    },
    'rename' => [UOJProblem::cur()->getCandidateDataManager(), 'rename'],
    'copy' => [UOJProblem::cur()->getCandidateDataManager(), 'copy'],
    'unlink' => [UOJProblem::cur()->getCandidateDataManager(), 'unlink'],
    'rmdir' => [UOJProblem::cur()->getCandidateDataManager(), 'rmdir'],
    'update' => [UOJProblem::cur()->getCandidateDataManager(), 'svnUpdate']
];

include(__DIR__.'/tinyfilemanager/tinyfilemanager.php');