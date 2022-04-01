<?php

class Paginator {
	public $n_rows;
	public $n_pages;
	public $page_len;
	public $cur_page;
	public $cur_start;
	public $max_extend;

	public $table_name;
	public $cond;
	public $col_names;
	public $tail;
	public $post_filter;

	public $table;

	public function getPage($page) {
		$cur_start = ($page - 1) * $this->page_len;
		$table = DB::selectAll([
			"select", DB::fields($this->col_names), "from", DB::query_str($this->table_name),
			"where", $this->cond,
			$this->tail, DB::limit($cur_start, $this->page_len)
		]);
		if ($this->post_filter === null) {
			return $table;
		} else {
			return array_filter($table, $this->post_filter);
		}
	}

	public function checkPageExists($page) {
		if ($page < 1 || $page > $this->n_pages) {
			return false;
		}
		if ($this->post_filter === null) {
			return true;
		}

		$post_filter = $this->post_filter;
		$cur_start = ($page - 1) * $this->page_len;
		for ($bg = 0; $bg < $this->page_len; $bg += 10) {
			$table = DB::selectAll([
				"select", DB::fields($this->col_names), "from", DB::query_str($this->table_name),
				"where", $this->cond,
				$this->tail, DB::limit($cur_start + $bg, min(10, $this->page_len - $bg))
			]);
			foreach ($table as $entry) {
				if ($post_filter($entry)) {
					return true;
				}
			}
		}
		return false;
	}
	
	public function __construct($config) {
	    $this->max_extend = isset($config['max_extend']) ? (int)$config['max_extend'] : 5;
		$this->post_filter = isset($config['post_filter']) ? $config['post_filter'] : null;
	    
		if (isset($config['data'])) {
			$this->n_pages = 1;
			$this->cur_page = 1;
			$this->cur_start = 0;
			$this->table = $config['data'];
			if ($this->post_filter !== null) {
				$this->table = array_filter($this->table, $this->post_filter);
			}
		} elseif (!isset($config['echo_full'])) {
		    $this->cur_page = UOJRequest::get('page', 'validateUInt', 1);
			if ($this->cur_page < 1) {
				$this->cur_page = 1;
			}
			$this->page_len = isset($config['page_len']) ? $config['page_len'] : 10;

			$this->table_name = $config['table_name'];
			$this->cond = $config['cond'];
			$this->col_names = $config['col_names'];
			$this->tail = $config['tail'];
		
			$this->n_rows = DB::selectCount([
                "select", "count(*)", "from", DB::bracket([
                    "select", "1", "from", DB::query_str($this->table_name),
                    "where", $this->cond,
                    DB::limit(max($this->cur_page + $this->max_extend + 1, $this->max_extend * 2 + 1) * $this->page_len)
                ]), "tmp_table_for_paginator"
            ]);
			
			$this->n_pages = max((int)ceil($this->n_rows / $this->page_len), 1);
			while ($this->n_pages > 1 && !$this->checkPageExists($this->n_pages)) {
				$this->n_pages--;
			}
			if ($this->cur_page > $this->n_pages) {
				$this->cur_page = $this->n_pages;
			}
			while (true) {
				$this->table = $this->getPage($this->cur_page);
				if ($this->table || $this->cur_page == 1) {
					break;
				}
				$this->cur_page--;
			}
			$this->cur_start = ($this->cur_page - 1) * $this->page_len;
		} else {
			$this->n_pages = 1;
			$this->cur_page = 1;
			$this->cur_start = 0;
			$this->table = DB::selectAll([
                "select", DB::fields($config['col_names']), "from", DB::query_str($config['table_name']),
                "where", $config['cond'],
                $config['tail']
            ]);
			if ($this->post_filter !== null) {
				$this->table = array_filter($this->table, $this->post_filter);
			}
		}
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
	
	public function get($limit = -1) {
		$cur_idx = $this->cur_start + 1;
		foreach ($this->table as $row) {
			if ($limit != -1 && $cur_idx - $this->cur_start > $limit) {
				break;
			}
			yield $cur_idx++ => $row;
		}
	}
	public function isEmpty() {
		return empty($this->table);
	}
	public function countInCurPage() {
		return count($this->table);
	}
	
	public function pagination() {
		if ($this->n_pages == 1) {
			return '';
		}
		$prev_page = false;
		$next_page = false;
		$main_lis = '';

		$page_st = $this->cur_page - $this->max_extend;
		$page_ed = $this->cur_page + $this->max_extend;
		if ($this->n_pages <= $this->max_extend * 2 + 1) {
			$page_st = 1;
			$page_ed = $this->n_pages;
		} else if ($page_st < 1) {
			$page_st = 1;
			$page_ed = $this->max_extend * 2 + 1;
		} else if ($page_ed > $this->n_pages) {
			$page_st = $this->n_pages - $this->max_extend * 2;
			$page_ed = $this->n_pages;
		}
		for ($i = $page_st; $i <= $page_ed; $i++) {
			if ($i == $this->cur_page) {
				$main_lis .= '<li class="active"><a href="'.$this->getPageUri($i).'">'.$i.'</a></li>';
			} else {
				if ($this->checkPageExists($i)) {
					$main_lis .= '<li><a href="'.$this->getPageUri($i).'">'.$i.'</a></li>';
					if ($i < $this->cur_page) {
						$prev_page = $i;
					} else if ($next_page === false) {
						$next_page = $i;
					}
				}
			}
		}

		$html = '<div class="text-center">';
		$html .= '<ul class="pagination top-buffer-no bot-buffer-sm">';
		if ($prev_page !== false) {
			$html .= '<li><a href="'.$this->getPageUri($prev_page).'"><span class="glyphicon glyphicon glyphicon-backward"></span></a></li>';
		} else {
			$html .= '<li class="disabled"><a><span class="glyphicon glyphicon glyphicon-backward"></span></a></li>';
		}
		$html .= $main_lis;
		if ($next_page !== false) {
			$html .= '<li><a href="'.$this->getPageUri($next_page).'"><span class="glyphicon glyphicon glyphicon-forward"></span></a></li>';
		} else {
			$html .= '<li class="disabled"><a><span class="glyphicon glyphicon glyphicon-forward"></span></a></li>';
		}
		$html .= '</ul>';
		$html .= '</div>';
		return $html;
	}
}
