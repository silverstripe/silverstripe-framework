<% if $IsReadonly %>
	<ul class="SelectionGroup<% if extraClass %> $extraClass<% end_if %>">
	<% loop $FieldSet %>
	<% if $Selected %>
		<li class="selected">
			$RadioLabel
			$FieldHolder
		</li>
	</ul>
	<% end_if %>
	<% end_loop %>
<% else %>
	<ul class="SelectionGroup<% if extraClass %> $extraClass<% end_if %>">
	<% loop $FieldSet %>
		<li <% if Selected %>class="selected"<% end_if %>>
			<label>{$RadioButton} {$RadioLabel}</label>
			<% if $FieldList %>
				$FieldHolder
			<% end_if %>
		</li>
	<% end_loop %>
	</ul>
<% end_if %>
