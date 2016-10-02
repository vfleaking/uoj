<?php
	$conds = array();
	
	$q_problem_id = isset($_GET['problem_id']) && validateUInt($_GET['problem_id']) ? $_GET['problem_id'] : null;
	$q_submitter = isset($_GET['submitter']) && validateUsername($_GET['submitter']) ? $_GET['submitter'] : null;
	$q_min_score = isset($_GET['min_score']) && validateUInt($_GET['min_score']) ? $_GET['min_score'] : null;
	$q_max_score = isset($_GET['max_score']) && validateUInt($_GET['max_score']) ? $_GET['max_score'] : null;
	$q_language = isset($_GET['language']) ? $_GET['language'] : null;
	if($q_problem_id != null) {
		$conds[] = "problem_id = $q_problem_id";
	}
	if($q_submitter != null) {
		$conds[] = "submitter = '$q_submitter'";
	}
	if ($q_min_score != null) {
		$conds[] = "score >= $q_min_score";
	}
	if ($q_max_score != null) {
		$conds[] = "score <= $q_max_score";
	}
	if ($q_language != null) {
		$conds[] = sprintf("language = '%s'", mysql_real_escape_string($q_language));
	}
	
	$html_esc_q_language = htmlspecialchars($q_language);
	
	if ($conds) {
		$cond = join($conds, ' and ');
	} else {
		$cond = '1';
	}
?>
<?php echoUOJPageHeader(UOJLocale::get('submissions')) ?>
<div class="hidden-xs">
	<?php if ($myUser != null): ?>
	<div class="pull-right">
		<a href="/submissions?submitter=<?= $myUser['username'] ?>" class="btn btn-primary btn-sm"><?= UOJLocale::get('problems::my submissions') ?></a>
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
			<input type="text" class="form-control input-sm" name="min_score" id="input-min_score" value="<?= $q_min_score ?>" maxlength="3" style="width:4em" placeholder="0" />
			<label for="input-max_score" class="control-label">~</label>
			<input type="text" class="form-control input-sm" name="max_score" id="input-max_score" value="<?= $q_max_score ?>" maxlength="3" style="width:4em" placeholder="100" />
		</div>
		<div id="form-group-language" class="form-group">
			<label for="input-language" class="control-label"><?= UOJLocale::get('problems::language')?>:</label>
			<input type="text" class="form-control input-sm" name="language" id="input-language" value="<?= $html_esc_q_language ?>" maxlength="10" style="width:8em" />
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
	echoSubmissionsList($cond, 'order by id desc', array('judge_time_hidden' => ''), $myUser);
?>
<?php echoUOJPageFooter() ?>
