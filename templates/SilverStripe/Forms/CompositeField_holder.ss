<$Tag class="CompositeField $extraClass <% if $ColumnCount %>multicolumn<% end_if %>" id="$HolderID">
	<% if $Tag == 'fieldset' && $Legend %>
		<legend>$Legend</legend>
	<% end_if %>

	$Field
</$Tag>
