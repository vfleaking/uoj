<?php
	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('svn');
	
	if (isSuperUser($myUser)) {
		$new_problem_form = new UOJForm('new_problem');
		$new_problem_form->handle = function() {
			DB::insert([
                "insert into problems",
                "(title, is_hidden, submission_requirement)",
                "values", DB::tuple(["New Problem", 1, "{}"])
            ]);
			$id = DB::insert_id();
			DB::insert([
                "insert into problems_contents",
                "(id, statement, statement_md)",
                "values", DB::tuple([$id, "", ""])
            ]);
			svnNewProblem($id);
		};
		$new_problem_form->submit_button_config['align'] = 'right';
		$new_problem_form->submit_button_config['class_str'] = 'btn btn-primary';
		$new_problem_form->submit_button_config['text'] = UOJLocale::get('problems::add new');
		$new_problem_form->submit_button_config['smart_confirm'] = '';
		
		$new_problem_form->runAtServer();
	}
	
	function getProblemTR($info) {
		$problem = new UOJProblem($info);

		$html = '<tr class="text-center">';
		if ($info['submission_id']) {
			$html .= '<td class="success">';
		} else {
			$html .= '<td>';
		}
		$html .= "#{$info['id']}</td>";
		$html .= '<td class="text-left">';
		if ($info['is_hidden']) {
			$html .= ' <span class="text-danger">[隐藏]</span> ';
		}
		$html .= $problem->getLink(['with' => 'none']);
		if (isset($_COOKIE['show_tags_mode'])) {
			foreach ($problem->queryTags() as $tag) {
				$html .= '<a class="uoj-problem-tag">'.'<span class="badge">'.HTML::escape($tag).'</span>'.'</a>';
			}
		}
		$html .= '</td>';
		if (isset($_COOKIE['show_submit_mode'])) {
			$perc = $info['submit_num'] > 0 ? round(100 * $info['ac_num'] / $info['submit_num']) : 0;
			$html .= '<td><a href="/submissions?problem_id='.$info['id'].'&min_score=100&max_score=100">&times;'.$info['ac_num'].'</a></td>';
			$html .= '<td><a href="/submissions?problem_id='.$info['id'].'">&times;'.$info['submit_num'].'</a></td>';
			$html .= '<td>';
			$html .= '<div class="progress bot-buffer-no">';
			$html .= '<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="'.$perc.'" aria-valuemin="0" aria-valuemax="100" style="width: '.$perc.'%; min-width: 20px;">';
			$html .= $perc.'%';
			$html .= '</div>';
			$html .= '</div>';
			$html .= '</td>';
		}
		$html .= '<td class="text-left">'.$problem->getZanBlock().'</td>';
		$html .= '</tr>';
		return $html;
	}
	
	$cond = [];
	
	$search_tag = UOJRequest::get('tag', 'is_string', null);

	$cur_tab = UOJRequest::get('tab', 'is_string', 'all');
	if ($cur_tab == 'template') {
		$search_tag = "模板题";
	}
	if (is_string($search_tag)) {
        $cond[] = [
            DB::rawvalue($search_tag), "in", DB::rawbracket([
                "select tag from problems_tags",
                "where", ["problems_tags.problem_id" => DB::raw("problems.id")]
            ])
        ];
	}
	
	$search_content = UOJRequest::get('search', 'is_string', '');
	$search_is_effective = false;
	if ($search_content !== '') {
		foreach (explode(' ', $search_content) as $key) {
			if (strlen($key) > 0) {
				$cond[] = DB::lor([
					[DB::instr(DB::raw('title'), $key), '>', 0],
					DB::exists([
						"select tag from problems_tags",
						"where", [
							[DB::instr(DB::raw('tag'), $key), '>', 0],
							"problems_tags.problem_id" => DB::raw("problems.id")
						]
					]),
					"id" => $key,
				]);
				$search_is_effective = true;
			}
		}
	}
	
	if (!$cond) {
		$cond = '1';
	}
	
	$header = '<tr>';
	$header .= '<th class="text-center" style="width:5em;">ID</th>';
	$header .= '<th>'.UOJLocale::get('problems::problem').'</th>';
	if (isset($_COOKIE['show_submit_mode'])) {
		$header .= '<th class="text-center" style="width:5em;">'.UOJLocale::get('problems::ac').'</th>';
		$header .= '<th class="text-center" style="width:5em;">'.UOJLocale::get('problems::submit').'</th>';
		$header .= '<th class="text-center" style="width:150px;">'.UOJLocale::get('problems::ac rate').'</th>';
	}
	$header .= '<th class="text-center" style="width:180px;">'.UOJLocale::get('appraisal').'</th>';
	$header .= '</tr>';
	
	$tabs_info = array(
		'all' => array(
			'name' => UOJLocale::get('problems::all problems'),
			'url' => "/problems"
		),
		'template' => array(
			'name' => UOJLocale::get('problems::template problems'),
			'url' => "/problems/template"
		)
	);

	$pag = new Paginator([
        'col_names' => ['*'],
        'table_name' => [
            "problems left join best_ac_submissions",
            "on", [
                "best_ac_submissions.submitter" => Auth::id(),
                "problems.id" => DB::raw("best_ac_submissions.problem_id")
            ]
        ],
        'cond' => $cond,
        'tail' => "order by id asc",
        'page_len' => 100,
		'post_filter' => function ($problem) {
			return (new UOJProblem($problem))->userCanView(Auth::user());
		}
    ]);

	if ($search_is_effective) {
		$search_summary = [
			'count_in_cur_page' => $pag->countInCurPage(),
			'first_a_few' => []
		];
		foreach ($pag->get(5) as $info) {
			$problem = new UOJProblem($info);
			$search_summary['first_a_few'][] = [
				'type' => 'problem',
				'id' => $problem->info['id'],
				'title' => $problem->getTitle()
			];
		}
		DB::insert([
			"insert into search_requests",
			"(created_at, remote_addr, type, cache_id, q, content, result)",
			"values", DB::tuple([DB::now(), UOJContext::remoteAddr(), 'search', 0, $search_content, UOJContext::requestURI(), json_encode($search_summary)])
		]);
	}
?>
<?php echoUOJPageHeader(UOJLocale::get('problems')) ?>
<div class="row">
	<div class="col-sm-4 bot-buffer-sm">
		<?= HTML::tablist($tabs_info, $cur_tab, 'nav-pills') ?>
	</div>
	<div class="col-sm-4 col-sm-push-4 text-right">
		<form class="form-inline" method="get">
			 <div class="input-group">
				<input type="text" class="form-control" name="search" placeholder="<?= UOJLocale::get('problems::problem title, ID or tag')?>" value="<?= HTML::escape($search_content) ?>" />  
				<div class="input-group-btn">
					<button type="submit" class="btn btn-search" id="submit-search"><span class="glyphicon glyphicon-search"></span></button>
				</div>
			</div>
		</form>
		<div class="checkbox">
		<label class="checkbox-inline" for="input-show_tags_mode"><input type="checkbox" id="input-show_tags_mode" <?= isset($_COOKIE['show_tags_mode']) ? 'checked="checked" ': ''?>/> <?= UOJLocale::get('problems::show tags') ?></label>
		<label class="checkbox-inline" for="input-show_submit_mode"><input type="checkbox" id="input-show_submit_mode" <?= isset($_COOKIE['show_submit_mode']) ? 'checked="checked" ': ''?>/> <?= UOJLocale::get('problems::show statistics') ?></label>
		</div>
	</div>
	<div class="col-sm-4 col-sm-pull-4">
		<?php echo $pag->pagination(); ?>
	</div>
</div>
<script type="text/javascript">
$('#input-show_tags_mode').click(function() {
	if (this.checked) {
		$.cookie('show_tags_mode', '', {path: '/problems', expires: 365});
	} else {
		$.removeCookie('show_tags_mode', {path: '/problems'});
	}
	location.reload();
});
$('#input-show_submit_mode').click(function() {
	if (this.checked) {
		$.cookie('show_submit_mode', '', {path: '/problems', expires: 365});
	} else {
		$.removeCookie('show_submit_mode', {path: '/problems'});
	}
	location.reload();
});
</script>
<?php

echo HTML::responsive_table($header, $pag->get(), [
	'table_attr' => [
		'class' => ['table', 'table-bordered', 'table-hover', 'table-striped']
	],
	'tr' => function($row, $idx) {
		return getProblemTR($row);
	}
]);

if (isSuperUser($myUser)) {
	$new_problem_form->printHTML();
}

echo $pag->pagination();

?>
<?php echoUOJPageFooter() ?>
