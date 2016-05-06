<ul class="list-unstyled selection-group">
	<% if $IsReadonly %>
		<% loop $FieldSet %>
			<% if $Selected %>
				<li class="selected selection-group__item">
					$RadioLabel
					<%-- Bypass composite item field and directly render child fields --%>
					<% loop $FieldList %>
						$Field
					<% end_loop %>
				</li>
			<% end_if %>
		<% end_loop %>
	<% else %>
		<% loop $FieldSet %>
			<li class="selection-group__item<% if $Selected %> selected<% end_if %>">
				{$RadioButton}{$RadioLabel}
				<%-- Bypass composite item field and directly render child fields --%>
				<% loop $FieldList %>
					$Field
				<% end_loop %>
			</li>
		<% end_loop %>
	<% end_if %>
</ul>
