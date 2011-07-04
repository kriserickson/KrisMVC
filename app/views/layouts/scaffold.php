<!DOCTYPE HTML>
<?php

// Variables passed into the view...

/** @var $tables array */
/** @var $display_name string */
/** @var $display_base_href string */
/** @var $table_width */
/** @var $body string */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title><?= $display_name ?> List</title>
    <link href="<?= KrisConfig::WEB_FOLDER ?>/css/scaffold.css" rel="stylesheet" type="text/css"/>
</head>
<body>

<table width="100%">
    <tr>
        <?php foreach ($tables as $link => $name): ?>
        <td width="<?= $table_width ?>"><a href="<?= $display_base_href.$link ?>"><?= $name ?></a></td>
        <?php endforeach; ?>
    </tr>
</table>

<h3><?= $display_name ?></h3>

<?= $body ?>

</body>
</html>