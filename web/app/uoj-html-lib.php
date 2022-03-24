<?php

function uojHandleAtSign($str, $uri) {
	$referrers = array();
	$res = preg_replace_callback('/@(@|[a-zA-Z0-9_]{1,20})/', function($matches) use(&$referrers) {
		if ($matches[1] === '@') {
			return '@';
		} else {
			$user = UOJUser::query($matches[1]);
			if ($user == null) {
				return $matches[0];
			} else {
				$referrers[$user['username']] = '';
				return '<span class="uoj-username" data-rating="'.$user['rating'].'">@'.$user['username'].'</span>';
			}
		}
	}, $str);
	
	$referrers_list = array();
	foreach ($referrers as $referrer => $val) {
		$referrers_list[] = $referrer;
	}
	
	return array($res, $referrers_list);
}

function uojStringPreview($str, $output_limit, $type='text') {
	switch ($type) {
		case 'text':
			return strOmit($str, $output_limit);
		case 'binary':
			return strOmit(chunk_split(chunk_split(bin2hex($str), 8, ' '), (8 + 1) * 8, "\n"), $output_limit * 2);
		default:
			return false;
	}
}

function uojFilePreview($file_name, $output_limit, $file_type='text') {
	return uojStringPreview(file_get_contents($file_name, false, null, 0, $output_limit + 4), $output_limit, $file_type);
}

function uojIncludeView($name, $view_params = array()) {
	extract($view_params);
	include $_SERVER['DOCUMENT_ROOT'].'/app/views/'.$name.'.php';
}

function redirectTo($url) {
	header('Location: '.$url);
	die();
}
function permanentlyRedirectTo($url) {
	header("HTTP/1.1 301 Moved Permanently"); 
	header('Location: '.$url);
	die();
}
function permanentlyRedirectToHTTPS() {
	if (UOJContext::isUsingHttps()) {
		return false;
	}
	permanentlyRedirectTo('https://' . UOJContext::httpHost() . UOJContext::requestURI());
	die();
}
function permanentlyRedirectToHTTP() {
	if (!UOJContext::isUsingHttps()) {
		return false;
	}
	permanentlyRedirectTo('http://' . UOJContext::httpHost() . UOJContext::requestURI());
	die();
}
function redirectToLogin() {
	if (UOJContext::isAjax()) {
		die('please <a href="'.HTML::url('/login').'">login</a>');
	} else {
		header('Location: '.HTML::url('/login'));
		die();
	}
}

function getUserLink($username, $rating = null) {
	if (validateUsername($username) && ($user = UOJUser::query($username))) {
		if ($rating == null) {
			$rating = $user['rating'];
        }
        return HTML::tag('span', [
                'class' => 'uoj-username',
                'data-rating' => $rating
            ],
            HTML::escape($username)
        );
	} else {
        return HTML::tag('span', [], HTML::escape($username));
	}
}

function getLongTablePageRawUri($page) {
		$path = strtok(UOJContext::requestURI(), '?');
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
function getLongTablePageUri($page) {
	return HTML::escape(getLongTablePageRawUri($page));
}

function echoLongTable($col_names, $table_name, $cond, $tail, $header_row, $print_row, $config) {
	$pag_config = $config;
	$pag_config['col_names'] = $col_names;
	$pag_config['table_name'] = $table_name;
	$pag_config['cond'] = $cond;
	$pag_config['tail'] = $tail;
	$pag = new Paginator($pag_config);

	$div_classes = isset($config['div_classes']) ? $config['div_classes'] : ['table-responsive'];
	$table_classes = isset($config['table_classes']) ? $config['table_classes'] : ['table', 'table-bordered', 'table-hover', 'table-striped', 'table-text-center'];
		
	echo '<div class="', implode(' ', $div_classes), '">';
	echo '<table class="', implode(' ', $table_classes), '">';
	echo '<thead>';
	echo $header_row;
	echo '</thead>';
	echo '<tbody>';

	foreach ($pag->get() as $idx => $row) {
		if (isset($config['get_row_index'])) {
			$print_row($row, $idx);
		} else {
			$print_row($row);
		}
	}
	if ($pag->isEmpty()) {
		echo HTML::tr_none();
	}

	echo '</tbody>';
	echo '</table>';
	echo '</div>';
	
	if (isset($config['print_after_table'])) {
		$fun = $config['print_after_table'];
		$fun();
	}
		
	echo $pag->pagination();
}

function getSubmissionStatusDetailsHTML($status, $status_details) {
	$html = '<td colspan="233" style="vertical-align: middle">';
	
	$fly = '<img src="//img.uoj.ac/utility/bear-flying.gif" alt="小熊像超人一样飞" class="img-rounded" />';
	$think = '<img src="//img.uoj.ac/utility/bear-thinking.gif" alt="小熊像在思考" class="img-rounded" />';
	
	if ($status == 'Judged') {
		$status_text = '<strong>Judged!</strong>';
		$status_img = $fly;
	} else {
		if ($status_details !== '') {
			$status_img = $fly;
			$status_text = HTML::escape($status_details);
		} else  {
			$status_img = $think;
			$status_text = $status;
		}
	}
	$html .= '<div class="uoj-status-details-img-div">' . $status_img . '</div>';
	$html .= '<div class="uoj-status-details-text-div">' . $status_text . '</div>';

	$html .= '</td>';
	return $html;
}

function echoSubmission($submission, $config, $viewer) {
    $usubm = new UOJSubmission($submission);
    $usubm->setProblem();
    $usubm->echoStatusTableRow($config, $viewer);
}

function echoSubmissionsList($cond, $tail, $config, $user) {
	$header_row = '<tr>';
	$col_names = [];
	$col_names[] = 'submissions.status_details';
	$col_names[] = 'submissions.status';
	$col_names[] = 'submissions.result_error';
	$col_names[] = 'submissions.score';
	$col_names[] = 'submissions.hide_score_to_others';
    $col_names[] = 'submissions.hidden_score';
    
    if (!isset($config['problem'])) {
        $config['problem'] = null;
    }
	
	if (!isset($config['id_hidden'])) {
		$header_row .= '<th>ID</th>';
		$col_names[] = 'submissions.id';
	}
	if (!isset($config['problem_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::problem').'</th>';
		$col_names[] = 'submissions.problem_id';
		$col_names[] = 'submissions.contest_id';
	}
	if (!isset($config['submitter_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::submitter').'</th>';
		$col_names[] = 'submissions.submitter';
	}
	if (!isset($config['result_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::result').'</th>';
	}
	if (!isset($config['used_time_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::used time').'</th>';
		$col_names[] = 'submissions.used_time';
	}
	if (!isset($config['used_memory_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::used memory').'</th>';
		$col_names[] = 'submissions.used_memory';
	}
	$header_row .= '<th>'.UOJLocale::get('problems::language').'</th>';
	$col_names[] = 'submissions.language';
	$header_row .= '<th>'.UOJLocale::get('problems::file size').'</th>';
	$col_names[] = 'submissions.tot_size';

	if (!isset($config['submit_time_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::submit time').'</th>';
		$col_names[] = 'submissions.submit_time';
	}
	if (!isset($config['judge_time_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::judge time').'</th>';
		$col_names[] = 'submissions.judge_time';
	}
	$header_row .= '</tr>';
	
	$table_name = isset($config['table_name']) ? $config['table_name'] : 'submissions';
    
    $cond = $cond === '1' ? [] : [DB::conds($cond)];
    $cond[] = UOJSubmission::sqlForUserCanView($user, $config['problem']);
    if ($config['problem']) {
        $cond[] = ['submissions.problem_id', '=', $config['problem']->info['id']];
    }
    if (count($cond) == 1) {
        $cond = $cond[0];
    } else {
        $cond = DB::land($cond);
    }
	
	$table_config = isset($config['table_config']) ? $config['table_config'] : null;
	
	echoLongTable($col_names, $table_name, $cond, $tail, $header_row,
		function($submission) use($config, $user) {
			echoSubmission($submission, $config, $user);
		}, $table_config);
}


function echoSubmissionContent($submission, $requirement) {
	$zip_file = new ZipArchive();
	$submission_content = json_decode($submission['content'], true);
	$zip_file->open(UOJContext::storagePath().$submission_content['file_name']);
	
	$config = [];
	foreach ($submission_content['config'] as $config_key => $config_val) {
		$config[$config_val[0]] = $config_val[1];
	}
	
	foreach ($requirement as $req) {
		if ($req['type'] == "source code") {
			$file_content = $zip_file->getFromName("{$req['name']}.code");
			$file_content = uojTextEncode($file_content, ['allow_CR' => true, 'html_escape' => true]);
			$file_language = htmlspecialchars($config["{$req['name']}_language"]);
			$footer_text = UOJLocale::get('problems::source code').', '.UOJLocale::get('problems::language').': '.$file_language;
			switch ($file_language) {
				case 'C++':
				case 'C++11':
					$sh_class = 'sh_cpp';
					break;
				case 'Python2.7':
				case 'Python3':
					$sh_class = 'sh_python';
					break;
				case 'Java7':
				case 'Java8':
					$sh_class = 'sh_java';
					break;
				case 'C':
					$sh_class = 'sh_c';
					break;
				case 'Pascal':
					$sh_class = 'sh_pascal';
					break;
				default:
					$sh_class = '';
					break;
			}
			echo '<div class="panel panel-info">';
			echo '<div class="panel-heading">';
			echo '<h4 class="panel-title">'.$req['name'].'</h4>';
			echo '</div>';
			echo '<div class="panel-body">';
			echo '<pre><code class="'.$sh_class.'">'.$file_content."\n".'</code></pre>';
			echo '</div>';
			echo '<div class="panel-footer">'.$footer_text.'</div>';
			echo '</div>';
		}
		else if ($req['type'] == "text") {
			$file_content = $zip_file->getFromName("{$req['file_name']}", 504);
			$file_content = strOmit($file_content, 500);
			$file_content = uojTextEncode($file_content, ['allow_CR' => true, 'html_escape' => true]);
			$footer_text = UOJLocale::get('problems::text file');
			echo '<div class="panel panel-info">';
			echo '<div class="panel-heading">';
			echo '<h4 class="panel-title">'.$req['file_name'].'</h4>';
			echo '</div>';
			echo '<div class="panel-body">';
			echo '<pre>'."\n".$file_content."\n".'</pre>';
			echo '</div>';
			echo '<div class="panel-footer">'.$footer_text.'</div>';
			echo '</div>';
		}
	}

	$zip_file->close();
}


class JudgmentDetailsPrinter {
	private $name;
	private $styler;
	private DOMDocument $dom;
	
	private $subtask_num;

	private function _get_attr(DOMElement $node, string $attr, $default='') {
		$val = $node->getAttribute($attr);
		if ($val === '') {
			$val = $default;
		}
		return $val;
	}

	private function _print_c(DOMElement $node) {
		foreach ($node->childNodes as $child) {
			if ($child->nodeName == '#text') {
				echo htmlspecialchars($child->nodeValue);
			} else {
				$this->_print($child);
			}
		}
	}
	private function _print(DOMElement $node) {
		if ($node->nodeName == 'error') {
			echo "<pre>\n";
			$this->_print_c($node);
			echo "\n</pre>";
		} elseif ($node->nodeName == 'tests') {
			if ($node->hasAttribute("errcode")) {
				echo "<pre>", "Judgment Failed. Error Code: ", $node->getAttribute("errcode"), ".</pre>";
			}
			echo '<div class="panel-group" id="', $this->name, '_details_accordion">';
			if ($this->styler->show_small_tip) {
				echo '<div class="text-right text-muted">', '小提示：点击横条可展开更详细的信息', '</div>';
			}
			$this->_print_c($node);
			echo '</div>';
		} elseif ($node->nodeName == 'subtask') {
			$subtask_info = $node->getAttribute('info');
			$subtask_num = $node->getAttribute('num');
			$subtask_score = $node->getAttribute('score');
			$subtask_time = $this->_get_attr($node, 'time', -1);
			$subtask_memory = $this->_get_attr($node, 'memory', -1);

			$subtask_type = $this->_get_attr($node, 'type', 'packed');
			$subtask_used_time_type = $this->_get_attr($node, 'used-time-type', 'sum');

			echo '<div class="panel ', $this->styler->getTestInfoClass($subtask_info), '">';
			
			$accordion_parent = "{$this->name}_details_accordion";
			$accordion_collapse =  "{$accordion_parent}_collapse_subtask_{$subtask_num}";
			$accordion_collapse_accordion =  "{$accordion_collapse}_accordion";
			echo 	'<div class="panel-heading" data-toggle="collapse" data-parent="#', $accordion_parent, '" data-target="#', $accordion_collapse, '">';
			
			echo 		'<div class="row">';
			echo 			'<div class="col-sm-2">';
			echo 				'<h3 class="panel-title">', 'Subtask #', $subtask_num, ': ', '</h3>';
			echo 			'</div>';
			
			if ($this->styler->show_score) {
				echo 		'<div class="col-sm-2">';
				$score_str = $subtask_type == 'min' ? 'min score' : 'score';
				echo        "{$score_str}: {$subtask_score}";
				echo 		'</div>';
				echo 		'<div class="col-sm-2">';
				echo 			htmlspecialchars($subtask_info);
				echo 		'</div>';
			} else {
				echo 		'<div class="col-sm-4">';
				echo 			htmlspecialchars($subtask_info);
				echo 		'</div>';
			}
			
			if ($subtask_time >= 0) {
				echo '<div class="col-sm-3">';
				$used_time_str = $subtask_used_time_type == 'max' ? 'max time' : 'total time';
				echo "{$used_time_str}: {$subtask_time}ms";
				echo '</div>';
			}

			if ($subtask_memory >= 0) {
				echo '<div class="col-sm-3">';
				echo 'memory: ', $subtask_memory, 'kb';
				echo '</div>';
			}

			echo 		'</div>';
			echo 	'</div>';
			
			echo 	'<div id="', $accordion_collapse, '" class="panel-collapse collapse">';
			echo 		'<div class="panel-body">';

			echo 			'<div id="', $accordion_collapse_accordion, '" class="panel-group">';
			$this->subtask_num = $subtask_num;
			$this->_print_c($node);
			$this->subtask_num = null;
			echo 			'</div>';

			echo 		'</div>';
			echo 	'</div>';
			echo '</div>';
		} elseif ($node->nodeName == 'test') {
			$test_info = $node->getAttribute('info');
			$test_num = $node->getAttribute('num');
			$test_score = $node->getAttribute('score');
			$test_time = $this->_get_attr($node, 'time', -1);
			$test_memory = $this->_get_attr($node, 'memory', -1);

			echo '<div class="panel ', $this->styler->getTestInfoClass($test_info), '">';
			
			$accordion_parent = "{$this->name}_details_accordion";
			if ($this->subtask_num != null) {
				$accordion_parent .= "_collapse_subtask_{$this->subtask_num}_accordion";
			}
			$accordion_collapse = "{$accordion_parent}_collapse_test_{$test_num}";
			if (!$this->styler->shouldFadeDetails($test_info)) {
				echo '<div class="panel-heading" data-toggle="collapse" data-parent="#', $accordion_parent, '" data-target="#', $accordion_collapse, '">';
			} else {
				echo '<div class="panel-heading">';
			}
			echo '<div class="row">';
			echo '<div class="col-sm-2">';
			if ($test_num > 0) {
				echo '<h4 class="panel-title">', 'Test #', $test_num, ': ', '</h4>';
			} else {
				echo '<h4 class="panel-title">', 'Extra Test:', '</h4>';
			}
			echo '</div>';
				
			if ($this->styler->show_score) {
				echo '<div class="col-sm-2">';
				echo 'score: ', $test_score;
				echo '</div>';
				echo '<div class="col-sm-2">';
				echo htmlspecialchars($test_info);
				echo '</div>';
			} else {
				echo '<div class="col-sm-4">';
				echo htmlspecialchars($test_info);
				echo '</div>';
			}
				
			if ($test_time >= 0) {
				echo '<div class="col-sm-3">';
				echo 'time: ', $test_time, 'ms';
				echo '</div>';
			}

			if ($test_memory >= 0) {
				echo '<div class="col-sm-3">';
				echo 'memory: ', $test_memory, 'kb';
				echo '</div>';
			}

			echo '</div>';
			echo '</div>';

			if (!$this->styler->shouldFadeDetails($test_info)) {
				$accordion_collapse_class = 'panel-collapse collapse';
				if ($this->styler->collapse_in) {
					$accordion_collapse_class .= ' in';
				}
				echo '<div id="', $accordion_collapse, '" class="', $accordion_collapse_class, '">';
				echo '<div class="panel-body">';

				$this->_print_c($node);

				echo '</div>';
				echo '</div>';
			}

			echo '</div>';
		} elseif ($node->nodeName == 'custom-test') {
			$test_info = $node->getAttribute('info');
			$test_time = $this->_get_attr($node, 'time', -1);
			$test_memory = $this->_get_attr($node, 'memory', -1);

			echo '<div class="panel ', $this->styler->getTestInfoClass($test_info), '">';
			
			$accordion_parent = "{$this->name}_details_accordion";
			$accordion_collapse = "{$accordion_parent}_collapse_custom_test";
			if (!$this->styler->shouldFadeDetails($test_info)) {
				echo '<div class="panel-heading" data-toggle="collapse" data-parent="#', $accordion_parent, '" data-target="#', $accordion_collapse, '">';
			} else {
				echo '<div class="panel-heading">';
			}
			echo '<div class="row">';
			echo '<div class="col-sm-2">';
			echo '<h4 class="panel-title">', 'Custom Test: ', '</h4>';
			echo '</div>';
				
			echo '<div class="col-sm-4">';
			echo htmlspecialchars($test_info);
			echo '</div>';
				
			if ($test_time >= 0) {
				echo '<div class="col-sm-3">';
				echo 'time: ', $test_time, 'ms';
				echo '</div>';
			}

			if ($test_memory >= 0) {
				echo '<div class="col-sm-3">';
				echo 'memory: ', $test_memory, 'kb';
				echo '</div>';
			}

			echo '</div>';
			echo '</div>';

			if (!$this->styler->shouldFadeDetails($test_info)) {
				$accordion_collapse_class = 'panel-collapse collapse';
				if ($this->styler->collapse_in) {
					$accordion_collapse_class .= ' in';
				}
				echo '<div id="', $accordion_collapse, '" class="', $accordion_collapse_class, '">';
				echo '<div class="panel-body">';

				$this->_print_c($node);

				echo '</div>';
				echo '</div>';

				echo '</div>';
			}
		} elseif ($node->nodeName == 'in') {
			echo '<h4 class="bot-buffer-sm">input:</h4>';
			echo "<pre>\n";
			$this->_print_c($node);
			echo "\n</pre>";
		} elseif ($node->nodeName == 'out') {
			echo '<h4 class="bot-buffer-sm">output:</h4>';
			echo "<pre>\n";
			$this->_print_c($node);
			echo "\n</pre>";
		} elseif ($node->nodeName == 'res') {
			echo '<h4 class="bot-buffer-sm">result:</h4>';
			echo "<pre>\n";
			if ($node->hasChildNodes()) {
				$this->_print_c($node);
			} else {
				echo "\n";
			}
			echo "</pre>";
		} else if ($node->nodeName == "info-block") {
			echo '<div>';
			if ($node->hasAttribute("title")) {
				if ($node->hasAttribute("size")) {
					echo '<div class="pull-right text-muted">';
					if ($node->getAttribute("size") <= 1) {
						echo '(', $node->getAttribute("size"),' byte)';
					} else {
						echo '(', $node->getAttribute("size"),' bytes)';
					}
					echo '</div>';
				}
				echo '<h4 class="bot-buffer-sm">', $node->getAttribute("title"), ":</h4>";
			}
			echo '<pre>', "\n";
			$this->_print_c($node);
			echo "\n</pre>";
			echo '</div>';
		} else {
			echo '<', $node->nodeName;
			foreach ($node->attributes as $attr) {
				echo ' ', $attr->name, '="', htmlspecialchars($attr->value), '"';
			}
			echo '>';
			$this->_print_c($node);
			echo '</', $node->nodeName, '>';
		}
	}

	public function __construct($details, $styler, $name) {
		$this->name = $name;
		$this->styler = $styler;
		$this->details = $details;
		$this->dom = new DOMDocument();
		if (!$this->dom->loadXML($this->details)) {
			throw new Exception("XML syntax error");
		}
		$this->details = '';
	}
	public function printHTML() {
		$this->subtask_num = null;
		$this->_print($this->dom->documentElement);
	}
}

function echoJudgmentDetails($raw_details, $styler, $name) {
	try {
		$printer = new JudgmentDetailsPrinter($raw_details, $styler, $name);
		$printer->printHTML();
	} catch (Exception $e) {
		echo 'Failed to show details';
	}
}

class SubmissionDetailsStyler {
	public $show_score = true;
	public $show_small_tip = true;
	public $collapse_in = false;
	public $fade_all_details = false;
	public function getTestInfoClass($info) {
		if ($info == 'Accepted' || $info == 'Extra Test Passed') {
			return 'panel-uoj-accepted';
		} elseif ($info == 'Time Limit Exceeded') {
			return 'panel-uoj-tle';
		} elseif ($info == 'Acceptable Answer') {
			return 'panel-uoj-acceptable-answer';
		} else {
			return 'panel-uoj-wrong';
		}
	}
	public function shouldFadeDetails($info) {
		return $this->fade_all_details || $info == 'Extra Test Passed';
	}
}
class CustomTestSubmissionDetailsStyler {
	public $show_score = true;
	public $show_small_tip = false;
	public $collapse_in = true;
	public $fade_all_details = false;
	public function getTestInfoClass($info) {
		if ($info == 'Success') {
			return 'panel-uoj-accepted';
		} elseif ($info == 'Time Limit Exceeded') {
			return 'panel-uoj-tle';
		} elseif ($info == 'Acceptable Answer') {
			return 'panel-uoj-acceptable-answer';
		} else {
			return 'panel-uoj-wrong';
		}
	}
	public function shouldFadeDetails($info) {
		return $this->fade_all_details;
	}
}
class HackDetailsStyler {
	public $show_score = false;
	public $show_small_tip = false;
	public $collapse_in = true;
	public $fade_all_details = false;
	public function getTestInfoClass($info) {
		if ($info == 'Accepted' || $info == 'Extra Test Passed') {
			return 'panel-uoj-accepted';
		} elseif ($info == 'Time Limit Exceeded') {
			return 'panel-uoj-tle';
		} elseif ($info == 'Acceptable Answer') {
			return 'panel-uoj-acceptable-answer';
		} else {
			return 'panel-uoj-wrong';
		}
	}
	public function shouldFadeDetails($info) {
		return $this->fade_all_details;
	}
}

function echoSubmissionDetails($submission_details, $name) {
	echoJudgmentDetails($submission_details, new SubmissionDetailsStyler(), $name);
}
function echoCustomTestSubmissionDetails($submission_details, $name) {
	echoJudgmentDetails($submission_details, new CustomTestSubmissionDetailsStyler(), $name);
}
function echoHackDetails($hack_details, $name) {
	echoJudgmentDetails($hack_details, new HackDetailsStyler(), $name);
}

function echoHack($hack, $config, $viewer) {
    $uhack = new UOJHack($hack);
    $uhack->setProblem();
    $uhack->setSubmission();
    $uhack->echoStatusTableRow($config, $viewer);
}
function echoHackListOnlyOne($hack, $config, $user) {
	echo '<div class="table-responsive">';
	echo '<table class="table table-bordered table-text-center">';
	echo '<thead>';
	echo '<tr>';
	if (!isset($config['id_hidden']))
		echo '<th>ID</th>';
	if (!isset($config['submission_id_hidden']))
		echo '<th>'.UOJLocale::get('problems::submission id').'</th>';
	if (!isset($config['problem_hidden']))
		echo '<th>'.UOJLocale::get('problems::problem').'</th>';
	if (!isset($config['hacker_hidden']))
		echo '<th>'.UOJLocale::get('problems::hacker').'</th>';
	if (!isset($config['owner_hidden']))
		echo '<th>'.UOJLocale::get('problems::owner').'</th>';
	if (!isset($config['result_hidden']))
		echo '<th>'.UOJLocale::get('problems::result').'</th>';
	if (!isset($config['submit_time_hidden']))
		echo '<th>'.UOJLocale::get('problems::submit time').'</th>';
	if (!isset($config['judge_time_hidden']))
		echo '<th>'.UOJLocale::get('problems::judge time').'</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>';
	echoHack($hack, $config, $user);
	echo '</tbody>';
	echo '</table>';
	echo '</div>';
}
function echoHacksList($cond, $tail, $config, $user) {
	$header_row = '<tr>';
	$col_names = [];
	
	$col_names[] = 'id';
	$col_names[] = 'success';
	$col_names[] = 'status';
	$col_names[] = 'judge_time';
	
	if (!isset($config['id_hidden'])) {
		$header_row .= '<th>ID</th>';
	}
	if (!isset($config['submission_id_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::submission id').'</th>';
		$col_names[] = 'submission_id';
	}
	if (!isset($config['problem_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::problem').'</th>';
		$col_names[] = 'problem_id';
	}
	if (!isset($config['hacker_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::hacker').'</th>';
		$col_names[] = 'hacker';
	}
	if (!isset($config['owner_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::owner').'</th>';
		$col_names[] = 'owner';
	}
	if (!isset($config['result_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::result').'</th>';
	}
	if (!isset($config['submit_time_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::submit time').'</th>';
		$col_names[] = 'submit_time';
	}
	if (!isset($config['judge_time_hidden'])) {
		$header_row .= '<th>'.UOJLocale::get('problems::judge time').'</th>';
	}
	$header_row .= '</tr>';

	if (!isSuperUser($user)) {
		if ($user != null) {
			$permission_cond = DB::lor([
                'is_hidden' => false,
                DB::land([
                    'is_hidden' => true, [
                        'problem_id', 'in', DB::rawbracket([
                            'select problem_id from problems_permissions',
                            'where', ['username' => $user['username']]
                        ])
                    ]
                ])
            ]);
		} else {
			$permission_cond = ['is_hidden' => false];
		}
		if ($cond !== '1') {
			$cond = [
                DB::conds($cond),
                DB::conds($permission_cond)
            ];
		} else {
			$cond = $permission_cond;
		}
	}

	echoLongTable($col_names, 'hacks', $cond, $tail, $header_row,
		function($hacks) use($config, $user) {
			echoHack($hacks, $config, $user);
		}, null);
}

function echoBlogTag($tag) {
	echo '<a class="uoj-blog-tag"><span class="badge">', HTML::escape($tag), '</span></a>';
}

function echoUOJPageHeader($page_title, $extra_config = []) {
	global $REQUIRE_LIB;
	$config = UOJContext::pageConfig();
	$config['REQUIRE_LIB'] = $REQUIRE_LIB;
	$config['PageTitle'] = $page_title;
	$config = array_merge($config, $extra_config);
	uojIncludeView('page-header', $config);
}
function echoUOJPageFooter($config = []) {
	global $REQUIRE_BUNDLE;
	$config['REQUIRE_BUNDLE'] = $REQUIRE_BUNDLE;
	uojIncludeView('page-footer', $config);
}
