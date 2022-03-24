<?php

class UOJProblemConfException extends Exception {
	public function __construct($message) {
		parent::__construct("<strong>problem.conf</strong> : $message");
	}
}
class UOJFileNotFoundException extends Exception {
	public function __construct($file_name) {
		parent::__construct("file <strong>" . HTML::escape($file_name) . '</strong> not found');
	}
}
class UOJSyncFailedException extends Exception {
	public function __construct($msg) {
		parent::__construct('同步失败：'.HTML::escape($msg));
	}
}
class UOJUploadFailedException extends Exception {
	public function __construct($msg) {
		parent::__construct('上传失败：'.HTML::escape($msg));
	}
}

class UOJInvalidArgumentException extends Exception {
	public function __construct($msg) {
		parent::__construct(HTML::escape($msg));
	}
}

class UOJNotLoginException extends Exception {
	public function __construct($msg = '未登录') {
		parent::__construct(HTML::escape($msg));
	}
}
