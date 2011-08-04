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
/** @var $search bool */
/** @var $search_values array */
/** @var $sort_field string */
/** @var $sort_order string */
/** @var $web_folder string */

// Types used in the view...
/** @var $model KrisCrudModel */
?>


<form id="displayForm" method="post" action="<?= $display_href ?>">
    <input type="hidden" name="current_page" value="<?= $current_page ?>"/>
    <input type="hidden" name="sort_field" id="sort_field" value="<?= $sort_field ?>"/>
    <input type="hidden" name="sort_order" id="sort_order" value="<?= $sort_order ?>"/>
    <table class="displayTable" summary="<?= $display_name ?>">
        <tr>
            <th class="rowHeader" colspan="1">
                <?php if (!$search): ?>
                    <input type="submit" name="search" value="Search"/>
                <?php else: ?>
                    <input type="submit" name="hide" value="Hide"/>
                <?php endif; ?>
            </th>

            <?php foreach ($columns as $column): ?>
            <th class="rowHeader"><a class="sortLink" href="<?= $display_href ?>/<?= $column['column_id'] ?>/<?= $column['sort'] ?>">
                <?= $column['column_name'] ?> <?= $column['sort_display'] ?></a>
            </th>
            <?php endforeach; ?>
        </tr>

        <?php if ($search): ?>
        <tr>
            <td class="filterColumn" colspan="1">
                <input type="submit" id="query" name="query" value="Query"/></td>
            <?php foreach (array_keys($columns) as $column): ?>
            <td class="filterColumn">
                <label for="search_<?= $column['column_id']; ?>"></label>
                <input class="searchInput" value="<?= $column['search_value'] ?>" type="text" id="search_<?= $column['column_id'] ?>" name="search_<?= $column['column_id'] ?>" size="12" maxlength="45" />
            </td>
            <?php endforeach; ?>
        </tr>
        <?php endif; ?>

        <?php foreach ($models as $model): ?>
        <tr class="displayRowEven">
            <td class="crudButtonsEven">
                <a href="<?= $view_href ?>/<?= $model['primary_key'] ?>"><img src="<?= $web_folder ?>/images/scaffold/view_button.png" height="16" width="16" border="0" alt="View" title="View"/></a>&nbsp;
                <a href="<?= $change_href ?>/<?= $model['primary_key'] ?>"><img src="<?= $web_folder ?>/images/scaffold/change_button.png" height="16" width="16" border="0" alt="Change" title="Change"/></a>&nbsp;
                <a href="<?= $delete_href ?>/<?= $model['primary_key'] ?>"><img src="<?= $web_folder ?>/images/scaffold/delete_button.png" height="16" width="16" border="0" alt="Delete" title="Delete"/></a>
            </td>
            <?php foreach ($model['column_values'] as $column): ?>
            <td class="displayCellEven"><?= $column['column_value']; ?></td>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
    </table>
    <hr size="1" class="horizontalRule"/>
    <table summary="navigation" class="navigationTable">
        <tr class="navigationRole">
            <td class="navigationButtons">
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
            <td class="pageStatus">
                Page:&nbsp;<?= $current_page + 1 ?>&nbsp;of&nbsp;<?= $number_of_pages ?>&nbsp;
                Records:&nbsp;<?= $total_records ; ?>
            </td>
        </tr>
    </table>
</form>



<!-- <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>-->
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.js"></script>
<script>
    $('.searchInput').keydown(function()
    {
        if (event.keyCode == '13') {
            $('#query').click();
            return false;
        }
    });
    $('.sortLink').click(function(event)
    {
        if ($('.searchInput[value!=""]').length > 0)
        {
            event.preventDefault();
            var urlSplit = $(this).attr('href').split('/');
            $('#sort_order').val(urlSplit[urlSplit.length - 1]);
            $('#sort_field').val(urlSplit[urlSplit.length - 2]);
            $('#query').click();
        }
        
    })
</script>