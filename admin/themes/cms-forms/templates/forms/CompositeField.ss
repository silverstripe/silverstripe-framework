<% if $Tag == 'fieldset' && $Legend %>
	<legend>$Legend</legend>
<% end_if %>
<% loop $FieldList %>
	$FieldHolder
<% end_loop %>
