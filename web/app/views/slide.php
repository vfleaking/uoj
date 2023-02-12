<?php
	$content_p = strpos($content, "\n");
	$slide_config = substr($content, 0, $content_p);
	$slide_content = substr($content, $content_p + 1);
	
	$slide_config = json_decode($slide_config, true);
	if ($slide_config === null) {
		die('error');
	}
	
	if (!isset($slide_config['theme'])) {
		$slide_config['theme'] = 'moon';
	}
	if (!in_array($slide_config['theme'], ['beige', 'black', 'blood', 'league', 'moon', 'night', 'serif', 'simple', 'sky', 'solarized', 'white'], true)) {
		die('invalid theme');
	}
?>
<!DOCTYPE html>
<html lang="zh-cn">
	<head>
		<meta charset="utf-8">

		<title><?= isset($PageTitle) ? $PageTitle : 'UOJ' ?> - <?= isset($PageMainTitle) ? $PageMainTitle : 'Universal Online Judge' ?></title>

		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />

		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, minimal-ui">

		<?= HTML::css_link('/css/reveal/reveal.css') ?>
		<link rel="stylesheet" type="text/css" href="<?= HTML::url("/css/reveal/theme/{$slide_config['theme']}.css") ?>" id="theme">

		<!-- Code syntax highlighting -->
		<?= HTML::css_link('/css/zenburn.css') ?>

		<!-- Printing and PDF exports -->
		<script>
			var link = document.createElement('link');
			link.rel = 'stylesheet';
			link.type = 'text/css';
			link.href = window.location.search.match(/print-pdf/gi) ? '<?= HTML::url('/css/reveal/print/pdf.css') ?>' : '<?= HTML::url('/css/reveal/print/paper.css') ?>';
			document.getElementsByTagName('head')[0].appendChild(link);
		</script>

		<!--[if lt IE 9]>
			<script src="<?= HTML::url('/js/html5shiv.js') ?>"></script>
		<![endif]-->
	</head>
	<body>
		<div class="reveal">
			<div class="slides"><?= $slide_content ?></div>
		</div>

		<script src="<?= HTML::url('/js/head.min.js') ?>"></script>
		<script src="<?= HTML::url('/js/reveal.js') ?>"></script>

		<script type="text/javascript">
			Reveal.initialize({
				controls: true,
				progress: true,
				history: true,
				center: true,
				help: true,

				transition: 'slide',
				
				mathjax3: {
					mathjax: '<?= HTML::url('/js/MathJax-3.2.2/es5/tex-mml-chtml.js') ?>',
					tex: {
						inlineMath: [
							[ '$', '$' ],
							[ '\\(', '\\)' ]
						]
					},
					options: {
						skipHtmlTags: [ 'script', 'noscript', 'style', 'textarea', 'pre' ]
					},
				},

				dependencies: [
					{ src: '<?= HTML::url('/js/classList.js') ?>', condition: function() { return !document.body.classList; } },
					{ src: '<?= HTML::url('/js/reveal/plugin/highlight/highlight.js') ?>', async: true, condition: function() { return !!document.querySelector( 'pre code' ); }, callback: function() { hljs.initHighlightingOnLoad(); } },
					{ src: '<?= HTML::url('/js/reveal/plugin/zoom-js/zoom.js') ?>', async: true },
					{ src: '<?= HTML::url('/js/reveal/plugin/notes/notes.js') ?>', async: true },
					{ src: '<?= HTML::url('/js/reveal/plugin/math/math.js') ?>', async: true }
				]
			});
		</script>
	</body>
</html>
