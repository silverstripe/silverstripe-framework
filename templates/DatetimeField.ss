<div id="$ID" class="form__fieldgroup<% if $extraClass %> $extraClass<% end_if %>">
	$DateField.SmallFieldHolder
	$TimeField.SmallFieldHolder
	<% if $HasTimezone %>
		$TimezoneField.Field
	<% end_if %>
</div>
