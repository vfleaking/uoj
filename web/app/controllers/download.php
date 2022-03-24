<?php

requirePHPLib('judger');

$auth = false;
if (UOJRequest::get('auth') === 'judger') {
	authenticateJudger() || UOJResponse::page403();
	$auth = true;
}

switch (UOJRequest::get('type')) {
	case 'problem':
		UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
		if (!$auth) {
			UOJProblem::cur()->userCanDownloadAttachments(Auth::user()) || UOJResponse::page404();
		}
		$file_name = UOJProblem::cur()->getDataFolderPath().'/download.zip';
		$download_name = 'problem-'.UOJProblem::info('id').'-attachment.zip';
		break;
	case 'problem-main-data':
		UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
		if (!$auth) {
			UOJProblem::cur()->userCanManage(Auth::user()) || UOJResponse::page404();
		}
		$file_name = UOJProblem::cur()->getDataZipPath();
		$download_name = 'problem-'.UOJProblem::info('id').'-main.zip';
		break;
	case 'submission':		
		if (!$auth) {
			isSuperUser(Auth::user()) || UOJResponse::page404();
		}
		$file_name = UOJContext::storagePath()."/submission/{$_GET['id']}/{$_GET['rand_str_id']}";
		$download_name = "submission.zip";
		break;
	case 'tmp':
		if (!$auth) {
			isSuperUser(Auth::user()) || UOJResponse::page404();
		}
		$file_name = UOJContext::storagePath()."/tmp/{$_GET['rand_str_id']}";
		$download_name = "tmp";
		break;
	case 'testlib.h':
		$file_name = UOJLocalRun::$judger_include_path.'/testlib.h';
		$download_name = 'testlib.h';
		break;
	case 'contestip':
		UOJContest::init(UOJRequest::get('id')) || UOJResponse::page404();
		if (!$auth) {
			UOJContest::cur()->userCanManage(Auth::user()) || UOJResponse::page404();
		}

		UOJResponse::echofile(fn() => echoIPsForContest(UOJContest::info()), [
			'mimetype' => 'text/csv',
			'attachment' => 'contest-'.UOJContest::info('id').'-ip.csv'
		]);
	case 'contest':
		UOJContest::init(UOJRequest::get('id')) || UOJResponse::page404();
		if (!$auth) {
			UOJContest::cur()->userCanManage(Auth::user()) || UOJResponse::page404();
		}

		UOJResponse::echofile(function() {
			echo json_encode(UOJContest::cur()->queryResult());
		}, [
			'mimetype' => 'application/json',
			'attachment' => 'contest-'.UOJContest::info('id').'-result.json'
		]);
	case 'problem-md':
		UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
		if (!$auth) {
			UOJProblem::cur()->userCanView(Auth::user()) || UOJResponse::page404();
		}
		UOJResponse::echofile(function() {
			$content = UOJProblem::cur()->queryContent();
			echo $content['statement_md'];
		}, [
			'mimetype' => 'text/markdown; charset=UTF-8',
		]);
		break;
	case 'blog-md':
		UOJBlog::init(UOJRequest::get('id')) || UOJResponse::page404();
		UOJBlog::cur()->belongsToUserBlog() || UOJResponse::page404();
		!UOJBlog::cur()->isDraft() || UOJResponse::page404();
		UOJBlog::cur()->isTypeB() || UOJResponse::page404();
		if (!$auth) {
			UOJBlog::cur()->userCanView(Auth::user()) || UOJResponse::page404();
		}
		UOJResponse::echofile(function() {
			$content = UOJBlog::cur()->queryContent();
			echo $content['content_md'];
		}, [
			'mimetype' => 'text/markdown; charset=UTF-8',
		]);
		break;
	case 'slide-yaml':
		UOJBlog::init(UOJRequest::get('id')) || UOJResponse::page404();
		UOJBlog::cur()->belongsToUserBlog() || UOJResponse::page404();
		!UOJBlog::cur()->isDraft() || UOJResponse::page404();
		UOJBlog::cur()->isTypeS() || UOJResponse::page404();
		if (!$auth) {
			UOJBlog::cur()->userCanView(Auth::user()) || UOJResponse::page404();
		}
		UOJResponse::echofile(function() {
			$content = UOJBlog::cur()->queryContent();
			echo $content['content_md'];
		}, [
			'mimetype' => 'text/yaml; charset=UTF-8',
		]);
		break;
	default:
		UOJResponse::page404();
}

UOJResponse::xsendfile($file_name, ['attachment' => $download_name]);