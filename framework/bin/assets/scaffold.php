<!DOCTYPE HTML>
<?php

// Variables passed into the view...

/** @var $tables array */
/** @var $display_name string */
/** @var $display_base_href string */
/** @var $table_width */
/** @var $body string */
/** @var $error string */
/** @var $uses_auth bool */
        
?>
<html>
<head>
    <title><?= $display_name ?> List</title>
    <link href="<?= KrisConfig::WEB_FOLDER ?>/css/scaffold.css" rel="stylesheet" type="text/css"/>
</head>
<body>

<?php if ($uses_auth) : ?>
<div class="logout"><a href="<?= KrisConfig::WEB_FOLDER.'/'.KrisConfig::AUTH_CONTROLLER.'/logout'; ?>">Logout</a></div>
<?php endif; ?>

<table width="100%">
    <tr>
        <?php foreach ($tables as $link => $name): ?>
        <td width="<?= $table_width ?>%"><a href="<?= $display_base_href.$link ?>"><?= $name ?></a></td>
        <?php endforeach; ?>
    </tr>
</table>

<h3><?= $display_name ?></h3>

<?php if (strlen($error) > 0) { echo '<div class="error">'.$error.'</div>'; } ?>


<?= $body ?>

</body>
</html>