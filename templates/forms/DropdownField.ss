<select $AttributesHTML>
<% control Options %>
	<option value="$Value"<% if Selected %> selected="selected"<% end_if %><% if Disabled %> disabled="disabled"<% end_if %>>$Title</option>
<% end_control %>
</select>