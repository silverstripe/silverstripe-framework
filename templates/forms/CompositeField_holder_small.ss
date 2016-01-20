<$Tag class="CompositeField $extraClass <% if ColumnCount %>multicolumn<% end_if %>">
	<% if $Tag == 'fieldset' && $Legend %>
		<legend>$Legend</legend>
	<% end_if %>

	<% loop FieldList %>
		<% if ColumnCount %>
			<div class="column-{$ColumnCount} $FirstLast">
				$SmallFieldHolder
			</div>
		<% else %>
			$SmallFieldHolder
		<% end_if %>
	<% end_loop %>
</$Tag>
