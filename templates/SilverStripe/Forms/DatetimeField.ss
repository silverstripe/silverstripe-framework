<div id="$ID" class="form__fieldgroup<% if $extraClass %> $extraClass<% end_if %>">
	$DateField.SmallFieldHolder
	$TimeField.SmallFieldHolder
	<% if $TimeZone %>
		$TimezoneField.Field
	<% end_if %>
</div>
