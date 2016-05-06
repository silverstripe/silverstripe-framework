<div class="form-group selection-group">
	<ul class="form__field-holder form__field-holder--no-label SelectionGroup<% if $extraClass %> $extraClass<% end_if %>">
		<% if $IsReadonly %>
			<% loop $FieldSet %>
				<% if $Selected %>
					<li class="selected selection-group__item">
						$RadioLabel
						$SmallFieldHolder
					</li>
				<% end_if %>
			<% end_loop %>
		<% else %>
			<% loop $FieldSet %>
				<li class="selection-group__item<% if $Selected %> selected<% end_if %>">
					{$RadioButton}{$RadioLabel}
					$SmallFieldHolder
				</li>
			<% end_loop %>
		<% end_if %>
	</ul>
</div>
