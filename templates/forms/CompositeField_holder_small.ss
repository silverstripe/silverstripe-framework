<$Tag class="CompositeField $extraClass <% if ColumnCount %>multicolumn<% end_if %>">
	<% if $Tag == 'fieldset' && $Legend %>
		<legend>$Legend</legend>
	<% end_if %>

	<% loop FieldList %>
		<% if $Top.ColumnCount %>
			<div class="column-{$Top.ColumnCount} $FirstLast">
				$SmallFieldHolder
			</div>
		<% else %>
			$SmallFieldHolder
		<% end_if %>
	<% end_loop %>
</$Tag>
