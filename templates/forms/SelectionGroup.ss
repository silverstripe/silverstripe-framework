<div class="form-group">
<% if $IsReadonly %>
	<ul class="form__field-holder form__field-holder--no-label SelectionGroup<% if extraClass %> $extraClass<% end_if %>">
	<% loop $FieldSet %>
	<% if $Selected %>
		<li$Selected>
			$RadioLabel
			$SmallFieldHolder
		</li>
	</ul>
	<% end_if %>
	<% end_loop %>
<% else %>
	<ul class="form__field-holder form__field-holder--no-label SelectionGroup<% if extraClass %> $extraClass<% end_if %>">
	<% loop $FieldSet %>
		<li$Selected>
			{$RadioButton}{$RadioLabel}
			<% if $FieldList %>
				$SmallFieldHolder
			<% end_if %>
		</li>
	<% end_loop %>
	</ul>
<% end_if %>
</div>
