<form id="displayForm" method="post" action="{{display_href}}">
    <input type="hidden" name="current_page" value="{{current_page}}"/>
    <input type="hidden" name="sort_field" id="sort_field" value="{{sort_field}}"/>
    <input type="hidden" name="sort_order" id="sort_order" value="{{sort_order}}"/>
    <table class="displayTable" summary="{{display_name}}">
        <tr>
            <th class="rowHeader" colspan="1">
                {{^search}}
                    <input type="submit" name="search" value="Search"/>
                {{/search}}
                {{#search}}
                    <input type="submit" name="hide" value="Hide"/>
                {{/search}}
            </th>

            {{#columns}}
            <th class="rowHeader"><a class="sortLink" href="{{display_href}}/{{column_id}}/{{sort}}">{{column_name}} {{{sort_display}}}</a>
            </th>
            {{/columns}}
        </tr>

        {{#search}}
        <tr>
            <td class="filterColumn" colspan="1">
                <input type="submit" id="query" name="query" value="Query"/></td>
            {{#columns}}
            <td class="filterColumn">
                <label for="search_{{column_id}}"></label>
                <input class="searchInput" value="{{search_value}}" type="text" id="search_{{column_id}}" name="search_{{column_id}}" size="12" maxlength="45" />
            </td>
            {{/columns}}


        </tr>
        {{/search}}

        {{#models}}
        <tr class="displayRowEven">
            <td class="crudButtonsEven">
                <a href="{{view_href}}/{{primary_key}}"><img src="{{web_folder}}/images/scaffold/view_button.png" height="16" width="16" border="0" alt="View" title="View"/></a>&nbsp;
                <a href="{{change_href}}/{{primary_key}}"><img src="{{web_folder}}/images/scaffold/change_button.png" height="16" width="16" border="0" alt="Change" title="Change"/></a>&nbsp;
                <a href="{{delete_href}}/{{primary_key}}"><img src="{{web_folder}}/images/scaffold/delete_button.png" height="16" width="16" border="0" alt="Delete" title="Delete"/></a>
            </td>
            {{#column_values}}
            <td class="displayCellEven">{{column_value}}</td>
            {{/column_values}}
        </tr>
        {{/models}}
    </table>
    <hr size="1" class="horizontalRule"/>
    <table summary="navigation" class="navigationTable">
        <tr class="navigationRole">
            <td class="navigationButtons">
                <input type="submit" name="first_page" value="<<"{{#prev_page_disabled}} disabled="disabled"{{/prev_page_disabled}}/>
                <input type="submit" name="prev_page" value="<"{{#prev_page_disabled}} disabled="disabled"{{/prev_page_disabled}}/>
                <input type="submit" name="add" value="Add"/>
                <input type="submit" name="next_page" value=">"{{#next_page_disabled}} disabled="disabled"{{/next_page_disabled}}/>
                <input type="submit" name="last_page" value=">>"{{#next_page_disabled}} disabled="disabled"{{/next_page_disabled}}/>
                <label for="goto">Go to</label>
                <select name="goto" id="goto" onchange="return this.form.submit();">
                    {{#pages}}
                    <option value="{{page}}"{{#page_selected}}selected="selected"{{/page_selected}}>{{display_page}}</option>
                    {{/pages}}
                </select>
            </td>
            <td class="pageStatus">
                Page:&nbsp;{{display_page}}&nbsp;of&nbsp;{{number_of_pages}}&nbsp;
                Records:&nbsp;{{total_records}}
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