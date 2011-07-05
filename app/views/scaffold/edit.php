<?php

// Variables passed into the view...

/** @var $display_name string */
/** @var $class KrisCrudModel */
/** @var $change_href string */
/** @var $display_href string */

?>

<table class="pme-main" summary="<?= $display_name ?>">
    <?php foreach ($class->GetDisplayAndDatabaseFields() as $fieldName => $fieldDisplay): ?>
    <tr>
        <td class="pme-key-odd"><?= $fieldDisplay ?></td>
        <td class="pme-key-even"><?= $class->GetEditValue($fieldName) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<hr size="1" class="pme-hr"/>

<button id="saveButton">Save</button><button id="applyButton">Apply</button><button id="cancelButton">Cancel</button>

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.js"></script>
<script>
    $('#saveButton').click(function() { window.location = '<?= $save_href ?>'; });
    $('#applyButton').click(function() { window.location = '<?= $save_href ?>'; });
    $('#cancelButton').click(function() { window.location = '<?= $display_href ?>'; });
</script>

