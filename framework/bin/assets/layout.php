<!DOCTYPE html>
<?php
/** @var $title string */
/** @var $page_list array */
/** @var $body string */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title><?= $title ?></title>
    <link href="<?= KrisConfig::WEB_FOLDER ?>/css/style.css" rel="stylesheet" type="text/css"/>
</head>
<body>

    <div class="mainBody">
        <?= $body ?>
    </div>

</body>
</html>