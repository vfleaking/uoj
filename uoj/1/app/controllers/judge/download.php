<?php
	requirePHPLib('judger');
	
	if (!authenticateJudger()) {
		become404Page();
	}
	
	switch ($_GET['type']) {
		case 'submission':		
			$file_name = UOJContext::storagePath()."/submission/{$_GET['id']}/{$_GET['rand_str_id']}";
			$download_name = "submission.zip";
			break;
		case 'tmp':
			$file_name = UOJContext::storagePath()."/tmp/{$_GET['rand_str_id']}";
			$download_name = "tmp";
			break;
		case 'problem':
			$id = $_GET['id'];
			if (!validateUInt($id) || !($problem = queryProblemBrief($id))) {
				become404Page();
			}
			$file_name = "/var/uoj_data/$id.zip";
			$download_name = "$id.zip";
			break;
		default:
			become404Page();
	}
	
	$finfo = finfo_open(FILEINFO_MIME);
	$mimetype = finfo_file($finfo, $file_name);
	if ($mimetype === false) {
		become404Page();
	}
	finfo_close($finfo);
	
	header("X-Sendfile: $file_name");
	header("Content-type: $mimetype");
	header("Content-Disposition: attachment; filename=$download_name");
?>
