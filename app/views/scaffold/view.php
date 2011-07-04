<?php

// Variables passed into the view...

/** @var $display_name string */
/** @var $class KrisCrudModel */


?>

<table class="pme-main" summary="<?= $display_name ?>">

<?php foreach ($class->GetDisplayFields() as $fieldName): ?>
    <tr>
        <td class="td.pme-key-odd"><?= $fieldName ?></td>
        <td class="td.pme-key-even"><?= $class->get($fieldName) ?></td>
    </tr>
<?php endforeach; ?>

</table>