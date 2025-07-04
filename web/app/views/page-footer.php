<?php
	if (!isset($ShowPageFooter)) {
		$ShowPageFooter = true;
	}
?>
			</div>
			<?php if ($ShowPageFooter): ?>
			<div class="uoj-footer">
				<?= UOJLocale::getLocaleSwitcher() ?>
				<?php if (UOJConfig::$data['switch']['ICP-license']): ?>
				<ul class="list-inline">
					<li>Universal Online Judge</li>|<li><a target="_blank" href="http://www.beian.gov.cn/portal/registerSystemInfo?recordcode=42010202000505" style="text-decoration:none;"><img src="<?= HTML::url('/pictures/beian.png') ?>" /> 鄂公网安备 42010202000505 号</a></li>|<li><a target="_blank" href="https://beian.miit.gov.cn/" style="text-decoration:none;">鄂ICP备 14016048 号</a></li>
				</ul>
				<?php else: ?>
				<ul class="list-inline"><li>Universal Online Judge</li></ul>
				<?php endif ?>
				<p>Server time: <?= UOJTime::$time_now_str ?></p>
			</div>
			<?php endif ?>
            <?php if (UOJNotice::shouldConstantlyCheckNotice()): ?>
                <script type="text/javascript">
                    <?php UOJNotice::printJS(); ?>
                </script>
            <?php endif ?>
			<?php foreach ($REQUIRE_BUNDLE as $name => $val): ?>
				<?= HTML::js_src("/dist/{$name}.bundle.js") ?>
			<?php endforeach ?>
		</div>
		<!-- /container -->
	</body>
</html>
