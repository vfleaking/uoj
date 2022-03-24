<div class="row">
    <div class="col-sm-3">
        <select class="form-control" id="input-show_problem" name="show_problem">
            <?php foreach ($options as $option): ?>
                <?= HTML::option($option['value'], $option['text'], $option['value'] == $chosen) ?>
            <?php endforeach ?>
        </select>
    </div>
    <div class="col-sm-push-6 col-sm-3">
        <div class="checkbox text-right">
            <label for="input-show_all_submissions"><?= HTML::checkbox('show_all_submissions', $show_all_submissions_status) ?> <?=UOJLocale::get('contests::show all submissions') ?></label>
        </div>
    </div>
</div>
<script type="text/javascript">
    $('#input-show_all_submissions').click(function() {
        if (this.checked) {
            $.cookie('show_all_submissions', '');
        } else {
            $.removeCookie('show_all_submissions');
        }
        location.reload();
    });
    $('#input-show_problem').change(function() {
        if ($(this).val() == 'all') {
            window.location.href = <?= json_encode(HTML::url('?')) ?>;
        } else {
            window.location.href = <?= json_encode(HTML::url('?')) ?> + '?p=' + $(this).val();
        }
    });
</script>

<?php 
    echoSubmissionsList(
        $conds, 'order by id desc', [
            'judge_time_hidden' => '',
            'problem_title' => [
                'with' => 'letter',
                'simplify' => true
            ]
        ],
        Auth::user()
    );
?>