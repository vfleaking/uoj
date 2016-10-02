<?php
	if (!isset($ShowPageFooter)) {
		$ShowPageFooter = true;
	}
?>
			</div>
			<?php if ($ShowPageFooter): ?>
			<div class="uoj-footer">
				<p><a href="<?= HTML::url(UOJContext::requestURI(), array('params' => array('locale' => 'zh-cn'))) ?>"><img src="//img.uoj.ac/utility/flags/24/cn.png" alt="中文" /></a> <a href="<?= HTML::url(UOJContext::requestURI(), array('params' => array('locale' => 'en'))) ?>"><img src="//img.uoj.ac/utility/flags/24/gb.png" alt="English" /></a></p>
				<ul class="list-inline"><li>Universal Online Judge</li>|<li><a href="http://www.miitbeian.gov.cn">鄂ICP备14016048号</a></li></ul>
				<p>Server time: <?= UOJTime::$time_now_str ?></p>
			</div>
			<?php endif ?>
		</div>
		<!-- /container -->
	</body>
</html>
