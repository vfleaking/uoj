<div class="row">
	<div class="col-sm-2 col-sm-push-5">
		<form id="form-gun">
			 <div class="input-group">
				<input type="number" class="form-control" id="input-gun" placeholder="滚到多少名？" />
				<span class="input-group-btn">
					<button class="btn btn-primary" type="submit">滚</button>
				</span>
			</div>
		</form>
	</div>
</div>

<div class="table-responsive top-buffer-md">
	<table id="standings" class="table table-bordered table-striped table-text-center table-vertical-middle"></table>
</div>

<script type="text/javascript">
contest_rule=<?=json_encode($contest['extra_config']['basic_rule'])?>;
bonus=<?=json_encode($contest['extra_config']['bonus'])?>;
standings_version=<?=$contest['extra_config']['standings_version']?>;
contest_id=<?=$contest['id']?>;
standings=<?=json_encode($standings)?>;
final_standings=<?=json_encode($final_standings)?>;
score=<?=json_encode($score)?>;
final_score=<?=json_encode($final_score)?>;
problems=<?=json_encode($contest_data['problems'])?>;
myname=<?=json_encode(Auth::id())?>;
$(document).ready(function() {
	standingsSkeleton();
	updateStanding();
});

update_status = 'idle';
standings_display = null;
score_display = null;

function isdiff(k, i) {
	return JSON.stringify(score[standings[k][2][0]][i]) !== JSON.stringify(final_score[standings[k][2][0]][i]);
}

function getClickable() {
	var res = [];
	for (var k = standings.length - 1; k >= 0; k--) {
		for (var i = 0; i < problems.length; i++) {
			if (isdiff(k, i)) {
				res.push([k, i]);
			}
		}
		if (res.length > 0) {
			return res;
		}
	}
	return [];
}

function isClickable(clks, k, i) {
	for (j = 0; j < clks.length; j++) {
		if (clks[j][0] == k && clks[j][1] == i) {
			return true;
		}
	}
	return false;
}

function unfrozen(k, i) {
	if (final_score[standings[k][2][0]][i] !== undefined) {
		score[standings[k][2][0]][i] = final_score[standings[k][2][0]][i];
	} else {
		delete score[standings[k][2][0]][i];
	}
}

function genClickCallback(k, i) {
	return function(e) {
		if (update_status != 'idle') {
			return;
		}
		if (!isClickable(getClickable(), k, i)) {
			return;
		}
		update_status = 'go';

		unfrozen(k, i);
		$('#tr-k-' + k + '-prob-' + i).addClass('warning');

		updateScoreTD(k, i);
		setTimeout(function() {
			$('#tr-k-' + k + '-prob-' + i).removeClass('warning');
			updateStanding();
			update_status = 'idle';
		}, 500);
	};
};
	

function people_cmp(lhs, rhs) {
	if (lhs[0] != rhs[0]) {
		return rhs[0] - lhs[0];
	} else if (lhs[1] != rhs[1]) {
		return lhs[1] - rhs[1];
	} else {
		return lhs[2][0] > rhs[2][0] ? 1 : -1;
	}
}

function calcCurrentStandings() {
	for (var k = 0; k < standings.length; k++) {
		var cur = standings[k];
		var p = cur[2][0];
		
		cur[0] = 0;
		cur[1] = 0;
		for (var i = 0; i < problems.length; i++) {
			if (score[p][i] !== undefined) {
				cur_row = score[p][i];
				cur[0] += cur_row[0];
				cur[1] += cur_row[1];
			}
		}
	}
	
	standings.sort(people_cmp);

	var is_same_rank = function(lhs, rhs) {
		return lhs[0] == rhs[0] && lhs[1] == rhs[1];
	};

	for (var k = 0; k < standings.length; k++) {
		if (k == 0 || !is_same_rank(standings[k - 1], standings[k])) {
			standings[k][3] = k + 1;
		} else {
			standings[k][3] = standings[k - 1][3];
		}
	}
}

function standingsSkeleton() {
	var header = $('<tr />');

	header.append(setACMStandingsTH(document.createElement('th'), -3));
	header.append(setACMStandingsTH(document.createElement('th'), -2));
	header.append(setACMStandingsTH(document.createElement('th'), -1));

	for (let i = 0; i < problems.length; i++) {
		let pid = problems[i];
		let th = document.createElement('th');
		th.id = 'th-prob-' + pid;
		header.append(setACMStandingsTH(th, i));
	}
	
	$('#standings').append(
		$('<thead />').append(header)
	);
	
	var tbody = $('<tbody />');
	
	for (var k = 0; k < standings.length; k++) {
		var tr = $('<tr />').css('height', '57px');
		tr.append('<td id="tr-k-vrank-' + k + '" />');
		tr.append('<td id="tr-k-name-' + k + '" />');
		tr.append('<td id="tr-k-total-' + k + '" />');
		for (var i = 0; i < problems.length; i++) {
			tr.append(
				$('<td id="tr-k-' + k + '-prob-' + i + '" />').click(genClickCallback(k, i))
			);
		}
		tbody.append(tr);
	}
	$('#standings').append(tbody);
}

function updateScoreTD(k, i, meta) {
	var row = standings[k];
	var col = score[row[2][0]][i];

	if (standings_display !== null) {
		if (JSON.stringify(row) === JSON.stringify(standings_display[k]) && JSON.stringify(col) === JSON.stringify(score_display[row[2][0]][i])) {
			return;
		}
	}

	if (meta === undefined) {
		meta = getACMStandingsMeta();
	}
	setACMStandingsTD(document.getElementById('tr-k-' + k + '-prob-' + i), row, i, meta)
}

function updateStanding() {
	calcCurrentStandings();

	var meta = getACMStandingsMeta();

	for (var i = 0; i < problems.length; i++) {
		let pid = problems[i];
		setACMStandingsTH(document.getElementById('th-prob-' + pid), i, meta);
	}

	var clks = getClickable();
	
	for (var k = 0; k < standings.length; k++) {
		var row = standings[k];
		setACMStandingsTD(document.getElementById('tr-k-vrank-' + k), row, -3, meta);
		setACMStandingsTD(document.getElementById('tr-k-name-' + k), row, -2, meta);
		setACMStandingsTD(document.getElementById('tr-k-total-' + k), row, -1, meta);
		for (var i = 0; i < problems.length; i++) {
			updateScoreTD(k, i, meta);
			if (isClickable(clks, k, i)) {
				$('#tr-k-' + k + '-prob-' + i).css('cursor', 'pointer').addClass('info');
			} else {
				$('#tr-k-' + k + '-prob-' + i).css('cursor', 'auto').removeClass('info');
			}
		}
	}

	standings_display = JSON.parse(JSON.stringify(standings));
	score_display = JSON.parse(JSON.stringify(score));
}

$('#form-gun').submit(function(e) {
	e.preventDefault();

	var val = parseInt($('#input-gun').val());
	if (val !== val) {
		alert('不是整数！');
	}
	$('#input-gun').val('');

	while (true) {
		var clks = getClickable();
		if (clks.length == 0) {
			break;
		}
		var [k, i] = clks[clks.length - 1];
		if (k < val) {
			break;
		}
		unfrozen(k, i);
		calcCurrentStandings();
	}
	updateStanding();
});

</script>
