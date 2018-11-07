<% loop $FieldList %>
	<% if $Up.ColumnCount %>
		<div class="column-{$Up.ColumnCount} $FirstLast">
			$FieldHolder
		</div>
	<% else %>
		$FieldHolder
	<% end_if %>
<% end_loop %>
