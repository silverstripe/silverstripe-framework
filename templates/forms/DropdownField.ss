<select $AttributesHTML>
<% control Options %>
	<option value="$Value"<% if Selected %> selected<% end_if %>>$Title</option>
<% end_control %>
</select>