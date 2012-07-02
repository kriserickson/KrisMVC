<?php

$WEB_FOLDER = '$WEB_FOLDER';
$body = '$body';
$title = '$title';
$footer = '$footer';

$html = <<<EOT
<!doctype html>
<?php /** @var $WEB_FOLDER */  /** @var $body */ /** @var $title */ /** @var $footer */ ?>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title><?= $title ?></title>
	<link rel="stylesheet" href="<?= $WEB_FOLDER ?>/css/main.css" type="text/css" media="screen" />
    <link rel="stylesheet" href="<?= $WEB_FOLDER ?>/css/form.css" type="text/css" media="screen" />
    <link rel="stylesheet" href="<?= $WEB_FOLDER ?>/css/login.css" type="text/css" media="screen" />
</head>
<body>
<div id="wrapper">

	<header>
		<h1><?= $title ?></h1>
	</header>

	<section id="main">
        <?= $body ?>
	</section>

    <?= $footer ?>


</div>

<script src="<?= $WEB_FOLDER ?>/js/jquery-1.6.2.min.js"></script>

<!-- Piwik -->
<script type="text/javascript">
var pkBaseURL = (("https:" == document.location.protocol) ? "https://localhost/piwik/" : "http://localhost/piwik/");
document.write(unescape("%3Cscript src='" + pkBaseURL + "piwik.js' type='text/javascript'%3E%3C/script%3E"));
</script><script type="text/javascript">
try {
var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", 1);
piwikTracker.trackPageView();
piwikTracker.enableLinkTracking();
} catch( err ) {}
</script><noscript><p><img src="http://localhost/piwik/piwik.php?idsite=1" style="border:0" alt="" /></p></noscript>
<!-- End Piwik Tracking Code -->


</body>
</html>
EOT;

$pageName = 'LayoutDefault';
$web_folder = 'Z:\restaurant';





?>