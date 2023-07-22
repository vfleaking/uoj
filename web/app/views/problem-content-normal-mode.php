<div class="tab-content">
	<div class="tab-pane active" id="tab-statement">
		<article class="uoj-article top-buffer-md"><?= $problem_presentation['statement'] ?></article>
	</div>
	<div class="tab-pane" id="tab-submit-answer">
		<div class="top-buffer-sm"></div>
		<?php if ($submission_error): ?>
		    <h3 class="text-center text-danger"><span class="glyphicon glyphicon-remove-sign"></span> <?= $submission_error ?></h3>
		<?php else: ?>
			<?php if ($submission_warning): ?>
				<h3 class="text-center text-warning"><span class="glyphicon glyphicon-exclamation-sign"></span> <?= $submission_warning ?></h3>
			<?php endif ?>
			<?php if (isset($forms['zip_answer'])): ?>
                <?php $forms['zip_answer']->printHTML(); ?>
			<?php endif ?>
			<?php if (isset($forms['zip_answer']) && isset($forms['answer'])): ?>
                <hr />
                <strong><?= UOJLocale::get('problems::or upload files one by one') ?><br /></strong>
			<?php endif ?>
			<?php if (isset($forms['answer'])): ?>
			    <?php $forms['answer']->printHTML(); ?>
            <?php endif ?>
		<?php endif ?>
	</div>
	<?php if (isset($forms['custom_test'])): ?>
        <div class="tab-pane" id="tab-custom-test">
            <div class="top-buffer-sm"></div>
            <?php $forms['custom_test']->printHTML(); ?>
        </div>
	<?php endif ?>
</div>
