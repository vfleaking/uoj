<?php

class UOJProblemDataDisplayer {
    public UOJProblem $problem;
	public ZipArchive $data_zip;
	public string $data_zip_root;
	public finfo $finfo;

	public array $problem_conf = [];
	public array $rest_data_files = [];
	public array $tabs = [];

	public function __construct($problem) {
        $this->problem = $problem;
		$this->data_zip = new ZipArchive;
		$ret = $this->data_zip->open($this->problem->getDataZipPath(), ZipArchive::RDONLY);
		if ($ret !== true) {
			UOJLog::error('zip open failed:', $this->problem->getDataZipPath(), $ret);
		}
		$this->data_zip_root = $this->problem->info['id'].'/';
		$this->finfo = new finfo(FILEINFO_MIME_ENCODING);

		for ($i = 0; $i < $this->data_zip->count(); $i++) {
			$name = $this->data_zip->getNameIndex($i);
			if (strStartWith($name, $this->data_zip_root) && !strEndWith($name, '/')) {
				$this->rest_data_files[substr($name, strlen($this->data_zip_root))] = true;
			}
		}
	}

	public function setProblemConf(array $problem_conf) {
		foreach ($problem_conf as $key => $val) {
			$this->problem_conf[$key] = ['val' => $val];
		}
	}

	public function setProblemConfRowStatus($key, $status) {
		$this->problem_conf[$key]['status'] = $status;
		return $this;
	}

	public function addTab($file_name, $fun) {
		$this->tabs[$file_name] = $fun;
		return $this;
	}
	public function echoAllTabs($active_tab) {
		$rest = array_keys($this->rest_data_files);
		natsort($rest);
		foreach (array_merge(array_keys($this->tabs), $rest) as $tab) {
			if ($tab !== $active_tab) {
				echo '<li>';
			} else {
				echo '<li class="active">';
			}
			echo '<a href="#">', HTML::escape($tab), '</a>', '</li>';
		}
	}
	public function echoFileNotFound($file_name) {
		echo '<h4>', HTML::escape($file_name), '<sub class="text-danger"> ', '文件未找到', '</sub></h4>';
	}
	public function echoFilePre($file_name, $max_len=1000) {
		$content = $this->getFile($file_name, $max_len + 4);
		if ($content === false) {
			$this->echoFileNotFound($file_name);
			return;
		}
		$mimetype = $this->finfo->buffer($content);
		if ($mimetype === false) {
			$this->echoFileNotFound($file_name);
			return;
		}

		echo '<h4>', HTML::escape($file_name), '<sub> ', $mimetype, '</sub></h4>';
		echo "<pre>\n";
		$type = $mimetype == 'binary' ? 'binary' : 'text';
		echo HTML::escape(uojStringPreview($content, $max_len, $type));
		echo "\n</pre>";
	}
	public function echoProblemConfTable() {
		echo '<table class="table table-bordered table-hover table-striped table-text-center">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>key</th>';
		echo '<th>value</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		foreach ($this->problem_conf as $key => $info) {
			$val = isset($info['val']) ? $info['val'] : '';
			if (!isset($info['status'])) {
				echo '<tr>';
				echo '<td>', HTML::escape($key), '</td>';
				echo '<td>', HTML::escape($val), '</td>';
				echo '</tr>';
			} elseif ($info['status'] == 'danger') {
				echo '<tr class="text-danger">';
				echo '<td>', HTML::escape($key), '</td>';
				echo '<td>', HTML::escape($val), ' <span class="glyphicon glyphicon-remove"></span>', '</td>';
				echo '</tr>';
			}
		}
		echo '</tbody>';
		echo '</table>';
	}

	public function displayFile($file_name) {
		if ($file_name === null) {
			$this->echoFileNotFound('');
		}
		if (isset($this->tabs[$file_name])) {
			($this->tabs[$file_name])($this);
		} elseif (isset($this->rest_data_files[$file_name])) {
			$this->echoFilePre($file_name);
		} else {
			$this->echoFileNotFound($file_name);
		}
	}

    public function isFile($name) {
		return $this->data_zip->statName($this->data_zip_root.$name);
    }

	public function getFile($name, $max_len=0) {
		return $this->data_zip->getFromName($this->data_zip_root.$name, $max_len);
	}
}