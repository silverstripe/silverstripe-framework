<div class="form-group">
	<% if Title %>
		<div class="form__field-label">$Title</div>
	<% end_if %>

	<div class="form__field-holder <% if Title %><% else %>form__field-holder--no-label <% end_if %>">
		<ul class="SelectionGroup <% if extraClass %>$extraClass <% end_if %>">
		<% if $IsReadonly %>
			<% loop $FieldSet %>
			<% if $Selected %>
			<li$Selected>
				$RadioLabel
				$SmallFieldHolder
			</li>
			<% end_if %>
			<% end_loop %>
		<% else %>
			<% loop $FieldSet %>
			<li$Selected>
				{$RadioButton}{$RadioLabel}
				<% if $FieldList %>
					$SmallFieldHolder
				<% end_if %>
			</li>
			<% end_loop %>
		<% end_if %>
		</ul>
	</div>
</div>
