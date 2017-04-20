<% loop $FieldList %>
	<% if $ColumnCount %>
		<div class="column-{$ColumnCount} $FirstLast">
			$FieldHolder
		</div>
	<% else %>
		$FieldHolder
	<% end_if %>
<% end_loop %>
