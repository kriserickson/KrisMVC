<!DOCTYPE HTML>
<html>
<head>
    <title>{{display_name}} List</title>
    <link href="{{web_folder}}/css/scaffold.css" rel="stylesheet" type="text/css"/>
</head>
<body>

{{#uses_auth}}
<div class="logout"><a href="{{web_folder}}/{{auth_controller}}/logout'; ?>">Logout</a></div>
{{/uses_auth}}

<table width="100%">
    <tr>
        {{#tables}}
        <td width="{{table_width}}>%"><a href="{{display_base_href}}{{link}}">{{name}}</a></td>
        {{/tables}}
    </tr>
</table>


<h3>{{display_name}}</h3>

{{#error}}
<div class="error">{{error}}</div>
{{/error}}

{{{body}}}

</body>
</html>