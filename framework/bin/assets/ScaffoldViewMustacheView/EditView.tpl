<link href="{{web_folder}}/css/jquery.wysiwyg.css" rel="stylesheet" type="text/css" />

<form id="displayForm" method="post" action="{{change_href}}" enctype="{{enctype}}">

<table class="displayTable" summary="{{display_name}}">
    {{#fields}}
    <tr>
        <td class="displayField">{{display}}</td>
        <td class="valueField">{{value}}</td>
    </tr>
    {{/fields}}
</table>

<hr size="1" class="horizontalRule"/>

<button id="saveButton" name="saveButton">Save</button>
{{#show_apply}}
<button id="applyButton" name="applyButton">Apply</button>
{{/show_apply}}
<button name="cancelButton" id="cancelButton">Cancel</button>
</form>

{{edit_javascript}}

