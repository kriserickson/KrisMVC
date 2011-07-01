<!DOCTYPE HTML>
<?php

// Variables passed into the view...

/** @var $displayName string */
/** @var $displayHref string */
/** @var $changeHref string */
/** @var $deleteHref string */
/** @var $viewHref string */
/** @var $columns array */
/** @var $sorted array */
/** @var $models array */
/** @var $current_page int */
/** @var $number_of_pages int */
/** @var $total_records int */

// Types used in the view...
/** @var $model KrisCrudModel */
?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title><?= $displayName ?></title>
    <link href="<?= KrisConfig::WEB_FOLDER ?>/css/scaffold.css" rel="stylesheet" type="text/css"/>
</head>
<body>

<h3><?= $displayName ?></h3>

<form method="post" action="<?= $displayHref ?>">
    <input type="hidden" name="current_page" value="<?= $current_page; ?>"/>
    <table class="pme-main" summary="<?= $displayName ?>">
        <tr class="pme-header">
            <th class="pme-header" colspan="1"><input type="submit" class="pme-search" name="search" value="Search"/>
            </th>

            <?php foreach ($columns as $columnName): ?>
            <th class="pme-header"><a class="pme-header"
                                      href="<?= $displayHref ?>/<?= $columnName ?>"><?= $columnName?></a></th>
            <?php endforeach; ?>
        </tr>

        <?php foreach ($sorted as $sortInfo): ?>
        <tr class="pme-sortinfo">
            <td class="pme-sortinfo" colspan="1"><a class="pme-sortinfo"
                                                    href="<?= $displayHref ?>/clear/sort/<?= $sortInfo['id'] ?>">Clear</a>
            </td>
            <td class="pme-sortinfo" colspan="<?= count($columns) ?>">Sorted
                By: <?= $sortInfo['name'] ?> <?= $sortInfo['ascending'] ? 'Ascending' : 'Descending' ?></td>
        </tr>
        <?php endforeach; ?>


        <?php foreach ($models as $model): ?>
        <tr class="pme-row-0">
            <td class="pme-navigation-0">
                <a class="pme-navigation-0" href="<?= $viewHref ?>/<?= $model->PrimaryKey() ?>"><img
                        class="pme-navigation-0" src="<?= KrisConfig::WEB_FOLDER ?>/images/scaffold/pme-view.png"
                        height="16" width="16" border="0" alt="View"
                        title="View"/></a>&nbsp;
                <a class="pme-navigation-0" href="<?= $changeHref ?>/<?= $model->PrimaryKey() ?>"><img
                        class="pme-navigation-0" src="<?= KrisConfig::WEB_FOLDER ?>/images/scaffold/pme-change.png"
                        height="16" width="16" border="0" alt="Change"
                        title="Change"/></a>&nbsp;
                <a class="pme-navigation-0" href="<?= $deleteHref ?>/<?= $model->PrimaryKey() ?>"><img
                        class="pme-navigation-0" src="<?= KrisConfig::WEB_FOLDER ?>/images/scaffold/pme-delete.png"
                        height="16" width="16" border="0" alt="Delete"
                        title="Delete"/></a>
            </td>
            <?php foreach (array_keys($columns) as $columnName): ?>
            <td class="pme-cell-0"><?= $model->get($columnName); ?></td>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
    </table>
    <hr size="1" class="pme-hr"/>
    <table summary="navigation" class="pme-navigation">
        <tr class="pme-navigation">
            <td class="pme-buttons">
                <input type="submit" name="first_page" value="<<" <?= $current_page == 0 ? 'disabled="disabled"' : '' ?>/>
                <input type="submit" name="prev_page" value="<" <?= $current_page == 0 ? 'disabled="disabled"' : '' ?>/>
                <input type="submit" name="add" value="Add"/>
                <input type="submit" name="next_page" value=">" <?= $current_page >= $number_of_pages - 1 ? 'disabled="disabled"' : '' ?>/>
                <input type="submit" name="last_page" value=">>"<?= $current_page >= $number_of_pages - 1 ? 'disabled="disabled"' : '' ?>/>
                <label for="goto">Go to</label>
                <select name="goto" id="goto" onchange="return this.form.submit();">
                    <?php for ($i = 0; $i < $number_of_pages; $i++): ?>
                    <option value="<?= $i ?>" <?= $i == $current_page ? 'selected="selected"' : '' ?>><?= $i + 1 ?></option>
                    <?php endfor; ?>
                </select>
            </td>
            <td class="pme-stats">
                Page:&nbsp;<?= $current_page + 1 ?>&nbsp;of&nbsp;<?= $number_of_pages ?>&nbsp; Records:&nbsp;<?= $total_records ; ?>
            </td>
        </tr>
    </table>
</form> 

</body>
</html>