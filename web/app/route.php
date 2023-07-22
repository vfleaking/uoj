<?php

Route::pattern('username', '[a-zA-Z0-9_]{1,20}');
Route::pattern('id', '[1-9][0-9]{0,9}');
Route::pattern('contest_id', '[1-9][0-9]{0,9}');
Route::pattern('tab', '\S{1,20}');
Route::pattern('rand_str_id', '[0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ]{20}');
Route::pattern('upgrade_name', '[a-zA-Z0-9_]{1,50}');
Route::pattern('path', '\S{1,256}');
Route::pattern('problem_data_branch', '(main|candidate)');

Route::group([
		'domain' => '('.UOJConfig::$data['web']['main']['host'].'|127.0.0.1'.')',
		'protocol' => UOJConfig::$data['web']['main']['protocol']
	], function() {
		Route::any('/', '/index.php');
		Route::any('/problems', '/problem_set.php');
		Route::any('/problems/template', '/problem_set.php?tab=template');
		Route::any('/problem/{id}', '/problem.php');
		Route::any('/problem/new', '/problem_init.php');
		Route::any('/problem/{id}/statement.md', '/download.php?type=problem-md');
		Route::any('/problem/{id}/statistics', '/problem_statistics.php');
		Route::any('/problem/{id}/manage/statement', '/problem_statement_manage.php');
		Route::any('/problem/{id}/manage/managers', '/problem_managers_manage.php');
		Route::any('/problem/{id}/manage/oss', '/problem_oss_manage.php');
		Route::any('/problem/{id}/manage/data', '/problem_data_manage.php');
		Route::any('/problem/{id}/manage/data/reset', '/problem_init.php');
		Route::any('/problem/{id}/manage/data/configure', '/problem_configure.php');
		Route::any('/problem/{id}/data/candidate', '/problem_data_candidate.php');
		Route::any('/problem/{id}/data/{problem_data_branch}/{path}', '/problem_data_files.php');
		Route::any('/download/problem/{id}/main.zip', '/download.php?type=problem-main-data');
		Route::any('/download/problem/{id}/attachment.zip', '/download.php?type=problem');
		
		Route::any('/contests', '/contests.php');
		Route::any('/contest/new', '/add_contest.php');
		Route::any('/contest/{id}', '/contest_inside.php');
		Route::any('/contest/{id}/registrants', '/contest_members.php');
		Route::any('/contest/{id}/register', '/contest_registration.php');
		Route::any('/contest/{id}/confirm-participation', '/contest_participate.php');
		Route::any('/contest/{id}/manage', '/contest_manage.php');
		Route::any('/contest/{id}/submissions', '/contest_inside.php?tab=submissions');
		Route::any('/contest/{id}/standings', '/contest_inside.php?tab=standings');
		Route::any('/contest/{id}/standings-unfrozen', '/contest_inside.php?tab=standings-unfrozen');
		Route::any('/contest/{id}/backstage', '/contest_inside.php?tab=backstage');
		Route::any('/contest/{contest_id}/problem/{id}', '/problem.php');
        Route::any('/contest/{contest_id}/problem/{id}/statistics', '/problem_statistics.php');
		Route::any('/download/contest/{id}/result.json', '/download.php?type=contest');
		
		Route::any('/submissions', '/submissions_list.php');
		Route::any('/submission/{id}', '/submission.php');
		Route::any('/submission-status-details', '/submission_status_details.php');
		
		Route::any('/hacks', '/hack_list.php');
		Route::any('/hack/{id}', '/hack.php');
		
		Route::any('/blogs', '/blogs.php');
		Route::any('/blog/{id}', '/blog_show.php');
		
		Route::any('/announcements', '/announcements.php');
		
		Route::any('/faq', '/faq.php');
		Route::any('/ranklist', '/ranklist.php?type=active-users-rating');
		Route::any('/ranklist/all-users', '/ranklist.php?type=rating');
		
		Route::any('/login', '/login.php');
		Route::any('/logout', '/logout.php');
		Route::any('/register', '/register.php');
		Route::any('/forgot-password', '/forgot_pw.php');
		Route::any('/reset-password', '/reset_pw.php');
		Route::any('/user/profile/{username}', '/user_info.php');
		Route::any('/user/modify-profile', '/change_user_info.php');
		Route::any('/user/msg', '/user_msg.php');
		Route::any('/user/system-msg', '/user_system_msg.php');
		Route::any('/super-manage(?:/{tab})?', '/super_manage.php');
		
		Route::any('/download.php', '/download.php');
		
		Route::any('/click-zan', '/click_zan.php');
        Route::any('/check-notice', '/check_notice.php');
	}
);

Route::post('/judge/submit', '/judge/submit.php');
Route::post('/judge/sync-judge-client', '/judge/sync_judge_client.php');

Route::post('/judge/download/submission/{id}/{rand_str_id}', '/download.php?type=submission&auth=judger');
Route::post('/judge/download/tmp/{rand_str_id}', '/download.php?type=tmp&auth=judger');
Route::post('/judge/download/problem/{id}', '/download.php?type=problem-main-data&auth=judger');
