<?php
	$conds = [];
    $config = ['judge_time_hidden' => true];
	
	$q_problem_id = UOJRequest::get('problem_id', 'validateUInt', null);
	$q_submitter = UOJRequest::get('submitter', 'validateUsername', null);
	$q_min_score = UOJRequest::get('min_score', 'validateUFloat', null);
	$q_max_score = UOJRequest::get('max_score', 'validateUFloat', null);
	$q_lang = UOJRequest::get('language', 'is_short_string', null);

	if (Auth::check()) {
		$my_submissions_url = HTML::url(UOJContext::requestURI(), [
			'params' => ['submitter' => Auth::id()]
		]);
	}
	
	if ($q_problem_id !== null) {
        $problem = UOJProblem::query($q_problem_id);
        if ($problem) {
            $config['problem'] = $problem;
        } else {
            $conds['problem_id'] = $q_problem_id;
        }
	}
	if ($q_submitter !== null) {
		$conds['submitter'] = $q_submitter;
	}
	if ($q_min_score !== null) {
		$conds[] = ['score', '>=', $q_min_score];
	}
	if ($q_max_score !== null) {
		$conds[] = ['score', '<=', $q_max_score];
	}
	if ($q_lang != null) {
		$matched_langs = UOJLang::getMatchedLanguages($q_lang);
		if (count($matched_langs) == 1) {
			$conds['language'] = $matched_langs[0];
		} else {
			$conds[] = ['language', 'in', DB::rawtuple($matched_langs)];
		}
    }
	
	if (!$conds) {
		$conds = '1';
	}
?>
<?php echoUOJPageHeader(UOJLocale::get('submissions')) ?>
<div class="hidden-xs">
	<?php if (isset($my_submissions_url)): ?>
	<div class="pull-right">
		<a href="<?= $my_submissions_url ?>" class="btn btn-primary btn-sm"><?= UOJLocale::get('problems::my submissions') ?></a>
	</div>
	<?php endif ?>
	<form id="form-search" class="form-inline" method="get">
		<div id="form-group-problem_id" class="form-group">
			<label for="input-problem_id" class="control-label"><?= UOJLocale::get('problems::problem id')?>:</label>
			<input type="text" class="form-control input-sm" name="problem_id" id="input-problem_id" value="<?= $q_problem_id ?>" maxlength="4" style="width:4em" />
		</div>
		<div id="form-group-submitter" class="form-group">
			<label for="input-submitter" class="control-label"><?= UOJLocale::get('username')?>:</label>
			<input type="text" class="form-control input-sm" name="submitter" id="input-submitter" value="<?= $q_submitter ?>" maxlength="20" style="width:10em" />
		</div>
		<div id="form-group-score" class="form-group">
			<label for="input-min_score" class="control-label"><?= UOJLocale::get('score range')?>:</label>
			<input type="text" class="form-control input-sm" name="min_score" id="input-min_score" value="<?= $q_min_score ?>" maxlength="20" style="width:4em" placeholder="0" />
			<label for="input-max_score" class="control-label">~</label>
			<input type="text" class="form-control input-sm" name="max_score" id="input-max_score" value="<?= $q_max_score ?>" maxlength="20" style="width:4em" placeholder="100" />
		</div>
		<div id="form-group-language" class="form-group">
			<label for="input-language" class="control-label"><?= UOJLocale::get('problems::language')?>:</label>
			<input type="text" class="form-control input-sm" name="language" id="input-language" value="<?= HTML::escape($q_lang) ?>" maxlength="10" style="width:8em" />
		</div>
		<button type="submit" id="submit-search" class="btn btn-default btn-sm"><?= UOJLocale::get('search')?></button>
	</form>
	<script type="text/javascript">
		$('#form-search').submit(function(e) {
			e.preventDefault();
			
			url = '/submissions';
			qs = [];
			$(['problem_id', 'submitter', 'min_score', 'max_score', 'language']).each(function () {
				if ($('#input-' + this).val()) {
					qs.push(this + '=' + encodeURIComponent($('#input-' + this).val()));
				}
			});
			if (qs.length > 0) {
				url += '?' + qs.join('&');
			}
			location.href = url;
		});
	</script>
	<div class="top-buffer-sm"></div>
</div>
<?php
	echoSubmissionsList($conds, 'order by id desc', $config, Auth::user());
?>
<?php echoUOJPageFooter() ?>
