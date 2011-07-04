<?php

// Variables passed into the view...

/** @var $display_name string */
/** @var $display_href string */
/** @var $change_href string */
/** @var $delete_href string */
/** @var $view_href string */
/** @var $columns array */
/** @var $sorted array */
/** @var $models array */
/** @var $current_page int */
/** @var $sort_ascending bool */
/** @var $number_of_pages int */
/** @var $total_records int */
/** @var $search bool|array */

// Types used in the view...
/** @var $model KrisCrudModel */
?>

<form id="displayForm" method="post" action="<?= $display_href ?>">
    <input type="hidden" name="current_page" value="<?= $current_page; ?>"/>
    <table class="pme-main" summary="<?= $display_name ?>">
        <tr class="pme-header">
            <th class="pme-header" colspan="1">
                <?php if (!$search): ?>
                    <input type="submit" name="search" value="Search"/>
                <?php else: ?>
                    <input type="submit" name="hide" value="Hide"/>
                <?php endif; ?>
            </th>

            <?php foreach ($columns as $columnId => $columnName): ?>
            <th class="pme-header"><a href="<?= $display_href ?>/<?= $columnId ?>/<?= $sort_ascending ? 'dec' : 'asc' ?>"><?= $columnName?></a></th>
            <?php endforeach; ?>
        </tr>

        <?php if ($search): ?>
        <tr class="pme-filter">
            <td class="pme-filter" colspan="1">
                <input type="submit" class="pme-query" name="query" value="Query"/></td>
            <?php foreach (array_keys($columns) as $columnId): ?>
            <td class="pme-filter">
                <label for="search_<?= $columnId; ?>"></label>
                <input class="pme-filter" value="<?= isset($search[$columnId]) ? $search[$columnId] : '' ?>" type="text" id="search_<?= $columnId; ?>" name="search_<?= $columnId; ?>" size="12" maxlength="45" />
            </td>
            <?php endforeach; ?>


        </tr>
        <?php endif; ?>

        <?php foreach ($models as $model): ?>
        <tr class="pme-row-even">
            <td class="pme-navigation-even">
                <a href="<?= $view_href ?>/<?= $model->PrimaryKey() ?>"><img
                        src="<?= KrisConfig::WEB_FOLDER ?>/images/scaffold/pme-view.png"
                        height="16" width="16" border="0" alt="View"
                        title="View"/></a>&nbsp;
                <a href="<?= $change_href ?>/<?= $model->PrimaryKey() ?>"><img
                        src="<?= KrisConfig::WEB_FOLDER ?>/images/scaffold/pme-change.png"
                        height="16" width="16" border="0" alt="Change"
                        title="Change"/></a>&nbsp;
                <a href="<?= $delete_href ?>/<?= $model->PrimaryKey() ?>"><img
                        src="<?= KrisConfig::WEB_FOLDER ?>/images/scaffold/pme-delete.png"
                        height="16" width="16" border="0" alt="Delete"
                        title="Delete"/></a>
            </td>
            <?php foreach (array_keys($columns) as $columnName): ?>
            <td class="pme-cell-even"><?= $model->get($columnName); ?></td>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
    </table>
    <hr size="1" class="pme-hr"/>
    <table summary="navigation" class="pme-navigation">
        <tr class="pme-navigation">
            <td class="pme-buttons">
                <input type="submit" name="first_page" value="<<" <?= $current_page == 0 ? 'disabled="disabled"'
                        : '' ?>/>
                <input type="submit" name="prev_page" value="<" <?= $current_page == 0 ? 'disabled="disabled"' : '' ?>/>
                <input type="submit" name="add" value="Add"/>
                <input type="submit" name="next_page" value=">" <?= $current_page >= $number_of_pages - 1
                        ? 'disabled="disabled"' : '' ?>/>
                <input type="submit" name="last_page" value=">>"<?= $current_page >= $number_of_pages - 1
                        ? 'disabled="disabled"' : '' ?>/>
                <label for="goto">Go to</label>
                <select name="goto" id="goto" onchange="return this.form.submit();">
                    <?php for ($i = 0; $i < $number_of_pages; $i++): ?>
                    <option value="<?= $i ?>" <?= $i == $current_page ? 'selected="selected"'
                            : '' ?>><?= $i + 1 ?></option>
                    <?php endfor; ?>
                </select>
            </td>
            <td class="pme-stats">
                Page:&nbsp;<?= $current_page + 1 ?>&nbsp;of&nbsp;<?= $number_of_pages ?>&nbsp;
                Records:&nbsp;<?= $total_records ; ?>
            </td>
        </tr>
    </table>
</form>



<!-- <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>-->
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.js"></script>
<script>
    $('.pme-filter').keydown(function()
    {
        if (event.keyCode == '13') {
            $('#displayForm').submit();
            return false;
        }
    });
</script>