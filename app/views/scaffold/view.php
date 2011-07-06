<?php
// Variables passed into the view...

/** @var $display_name string */
/** @var $class KrisCrudModel */
/** @var $form_href string */
/** @var $changeDeleteButton string */

?>
<table class="displayTable" summary="<?= $display_name ?>">
    <?php foreach ($class->GetDisplayAndDatabaseFields() as $fieldName => $fieldDisplay): ?>
    <tr>
        <td class="displayField"><?= $fieldDisplay ?></td>
        <td class="valueField"><?= $class->GetDisplayValue($fieldName) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<hr size="1" class="horizontalRule"/>

<form id="displayForm" method="post" action="<?= $form_href ?>">
    <?= $changeDeleteButton ?><button name="cancelButton" id="cancelButton">Cancel</button>
</form>
