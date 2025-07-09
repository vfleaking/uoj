<?php if ($contest['frozen'] && $contest['frozen_time'] !== false): ?>
<h4 class="text-center text-danger">封榜于 <?= $contest['frozen_time']->format('Y-m-d H:i:s') ?></h4>
<?php endif ?>

<div id="standings"></div>

<div class="table-responsive">
	<table id="standings-table" class="table table-bordered table-striped table-text-center table-vertical-middle"></table>
</div>

<script type="text/javascript">
contest_rule=<?=json_encode($contest['extra_config']['basic_rule'])?>;
bonus=<?=json_encode($contest['extra_config']['bonus'])?>;
standings_version=<?=$contest['extra_config']['standings_version']?>;
contest_id=<?=$contest['id']?>;
standings=<?=json_encode($standings)?>;
score=<?=json_encode($score)?>;
problems=<?=json_encode($contest_data['problems'])?>;
myname=<?=json_encode(Auth::id())?>;
$(document).ready(showStandings());
</script>
