<?php

class UOJException extends Exception {
}

class UOJProblemConfException extends UOJException {
	public function __construct($message) {
		parent::__construct("<strong>problem.conf</strong> : $message");
	}
}

class UOJFileNotFoundException extends UOJException {
	public function __construct($file_name) {
		parent::__construct("file <strong>" . HTML::escape($file_name) . '</strong> not found');
	}
}

class UOJSyncFailedException extends UOJException {
	public function __construct($msg) {
		parent::__construct('同步失败：'.HTML::escape($msg));
	}
}

class UOJUploadFailedException extends UOJException {
	public function __construct($msg) {
		parent::__construct('上传失败：'.HTML::escape($msg));
	}
}

class UOJSubmissionFailedException extends UOJException {
	public function __construct($msg) {
		parent::__construct('提交失败：'.HTML::escape($msg));
	}
}

class UOJInvalidArgumentException extends UOJException {
	public function __construct($msg) {
		parent::__construct(HTML::escape($msg));
	}
}

class UOJNotLoginException extends UOJException {
	public function __construct($msg = '未登录') {
		parent::__construct(HTML::escape($msg));
	}
}
