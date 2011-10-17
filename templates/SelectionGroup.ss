<% if IsReadonly %>
	<ul class="SelectionGroup$extraClass">
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
	<ul class="SelectionGroup$extraClass"><% control FieldSet %><li$Selected>{$RadioButton}{$RadioLabel}{$FieldHolder}</li><% end_control %></ul>
<% end_if %>