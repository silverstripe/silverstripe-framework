<% if IsReadonly %>
	<ul class="SelectionGroup<% if extraClass %> $extraClass<% end_if %>">
	<% control FieldSet %>
	<% if Selected %>
	<li$Selected>
		$RadioLabel
		$FieldHolder
	</li>
	</ul>
	<% end_if %>
	<% end_control %>
<% else %>
	<ul class="SelectionGroup<% if extraClass %> $extraClass<% end_if %>"><% control FieldSet %><li$Selected>{$RadioButton}{$RadioLabel}{$FieldHolder}</li><% end_control %></ul>
<% end_if %>