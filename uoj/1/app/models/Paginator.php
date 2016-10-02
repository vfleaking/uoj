<?php

class Paginator {
	public $n_rows;
	public $n_pages;
	public $page_len;
	public $cur_page;
	public $cur_start;
	public $max_extend;
	public $table;
	
	public function __construct($config) {
		if (!isset($config['echo_full'])) {
			$this->n_rows = DB::selectCount("select count(*) from {$config['table_name']} where {$config['cond']}");
			
			$this->page_len = isset($config['page_len']) ? $config['page_len'] : 10;
			
			$this->n_pages = max((int)ceil($this->n_rows / $this->page_len), 1);

			$this->cur_page = validateUInt($_GET['page']) ? (int)$_GET['page'] : 1;
			if ($this->cur_page < 1) {
				$this->cur_page = 1;
			} else if ($this->cur_page > $this->n_pages) {
				$this->cur_page = $this->n_pages;
			}
			$this->cur_start = ($this->cur_page - 1) * $this->page_len;
	
			$this->table = DB::selectAll("select ".join($config['col_names'], ',')." from {$config['table_name']} where {$config['cond']} {$config['tail']} limit {$this->cur_start}, {$this->page_len}");
		} else {
			$this->n_pages = 1;
			$this->cur_page = 1;
			$this->cur_start = ($this->cur_page - 1) * $this->page_len;
			$this->table = DB::selectAll("select ".join($config['col_names'], ',')." from {$config['table_name']} where {$config['cond']} {$config['tail']}");
		}
		
		$this->max_extend = isset($config['max_extend']) ? (int)$config['max_extend'] : 5;
	}
	
	public function getPageRawUri($page) {
		$path = strtok($_SERVER["REQUEST_URI"], '?');
		$query_string = strtok('?');
		parse_str($query_string, $param);
		
		$param['page'] = $page;
		if ($page == 1)
			unset($param['page']);
		
		if ($param) {
			return $path . '?' . http_build_query($param);
		} else {
			return $path;
		}
	}
	public function getPageUri($page) {
		return HTML::escape($this->getPageRawUri($page));
	}
	
	public function get() {
		$cur_idx = $this->cur_start + 1;
		foreach ($this->table as $idx => $row) {
			yield $cur_idx++ => $row;
		}
	}
	public function isEmpty() {
		return empty($this->table);
	}
	
	public function pagination() {
		if ($this->n_pages == 1) {
			return '';
		}
		$html = '<div class="text-center">';
		$html .= '<ul class="pagination top-buffer-no bot-buffer-sm">';
		if ($this->cur_page > 1) {
			$html .= '<li><a href="'.$this->getPageUri($this->cur_page - 1).'"><span class="glyphicon glyphicon glyphicon-backward"></span></a></li>';
		} else {
			$html .= '<li class="disabled"><a><span class="glyphicon glyphicon glyphicon-backward"></span></a></li>';
		}
			
		for ($i = max($this->cur_page - $this->max_extend, 1); $i <= min($this->cur_page + $this->max_extend, $this->n_pages); $i++) {
			if ($i == $this->cur_page) {
				$html .= '<li class="active"><a href="'.$this->getPageUri($i).'">'.$i.'</a></li>';
			} else {
				$html .= '<li><a href="'.$this->getPageUri($i).'">'.$i.'</a></li>';
			}
		}
		if ($this->cur_page < $this->n_pages) {
			$html .= '<li><a href="'.$this->getPageUri($this->cur_page + 1).'"><span class="glyphicon glyphicon glyphicon-forward"></span></a></li>';
		} else {
			$html .= '<li class="disabled"><a><span class="glyphicon glyphicon glyphicon-forward"></span></a></li>';
		}
		$html .= '</ul>';
		$html .= '</div>';
		return $html;
	}
}
