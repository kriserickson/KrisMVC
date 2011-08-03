<table class="displayTable" summary="{{display_name}}">
    {{#fields_values}}
    <tr>
        <td class="displayField">{{display}}</td>
        <td class="valueField">{{value}}</td>
    </tr>
    {{/fields_values}}
</table>

<hr size="1" class="horizontalRule"/>

<form id="displayForm" method="post" action="{{form_href}}">
    {{change_delete_button}}
    <button name="cancelButton" id="cancelButton">Cancel</button>
</form>
