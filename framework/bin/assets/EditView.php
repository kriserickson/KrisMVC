<?php

// Variables passed into the view...

/** @var $display_name string */
/** @var $class KrisCrudModel */
/** @var $change_href string */
/** @var $display_href string */
/** @var $change_href string */
/** @var $enctype string */
/** @var $show_apply bool */
?>
<link href="<?= KrisConfig::WEB_FOLDER ?>/css/jquery.wysiwyg.css" rel="stylesheet" type="text/css" />

<form id="displayForm" method="post" action="<?= $change_href ?>" enctype="<?= $enctype ?>">

<table class="displayTable" summary="<?= $display_name ?>">
    <?php foreach ($class->GetDisplayAndDatabaseFields() as $fieldName => $fieldDisplay): ?>
    <tr>
        <td class="displayField"><?= $fieldDisplay ?></td>
        <td class="valueField"><?= $class->GetEditValue($fieldName) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<hr size="1" class="horizontalRule"/>

<button id="saveButton" name="saveButton">Save</button>
<?php if ($show_apply): ?>
<button id="applyButton" name="applyButton">Apply</button>
<?php endif; ?>
<button name="cancelButton" id="cancelButton">Cancel</button>
</form>

<?= $class->GetJavascript('edit'); ?>

