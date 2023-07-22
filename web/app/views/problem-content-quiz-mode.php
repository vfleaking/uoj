<div class="tab-content">
	<div class="tab-pane active" id="tab-statement">
		<article class="uoj-article top-buffer-md">
            <?php if ($submission_error): ?>
                <h3 class="text-center text-danger"><span class="glyphicon glyphicon-remove-sign"></span> <?= $submission_error ?></h3>
            <?php elseif ($submission_warning): ?>
				<h3 class="text-center text-warning"><span class="glyphicon glyphicon-exclamation-sign"></span> <?= $submission_warning ?></h3>
			<?php endif ?>
            <?php $forms['quiz']->printHTML() ?>
        </article>
	</div>
</div>
