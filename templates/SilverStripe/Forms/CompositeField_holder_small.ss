<$Tag class="CompositeField $extraClass <% if $ColumnCount %>multicolumn<% end_if %>" id="$HolderID">
	<% if $Tag == 'fieldset' && $Legend %>
		<legend>$Legend</legend>
	<% end_if %>

	<% loop $FieldList %>
		<% if $Up.ColumnCount %>
			<div class="column-{$Up.ColumnCount} $FirstLast">
				$SmallFieldHolder
			</div>
		<% else %>
			$SmallFieldHolder
		<% end_if %>
	<% end_loop %>
</$Tag>
