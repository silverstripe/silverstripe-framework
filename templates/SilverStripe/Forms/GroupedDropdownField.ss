<select $AttributesHTML>
	<% loop $Options %>
		<% include SilverStripe/Forms/GroupedDropdownFieldOption %>
	<% end_loop %>
</select>
