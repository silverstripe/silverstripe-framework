<% if $IsReadonly %>
	<ul class="SelectionGroup<% if extraClass %> $extraClass<% end_if %>">
	<% loop $FieldSet %>
	<% if $Selected %>
		<li$Selected>
			$RadioLabel
			$FieldHolder
		</li>
	</ul>
	<% end_if %>
	<% end_loop %>
<% else %>
	<ul class="SelectionGroup<% if extraClass %> $extraClass<% end_if %>">
	<% loop $FieldSet %>
		<li$Selected>
			{$RadioButton}{$RadioLabel}
			<% if $FieldList %>
				$FieldHolder
			<% end_if %>
		</li>
	<% end_loop %>
	</ul>
<% end_if %>
