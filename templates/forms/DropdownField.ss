<select id="$ID" class="dropdown$extraClass" name="$Name"<% if TabIndex %> tabindex="$TabIndex"<% end_if %><% if isDisabled %> disabled<% end_if %>>
<% control Options %>
	<option value="$Value"<% if Selected %> selected<% end_if %>>$Title</option>
<% end_control %>
</select>