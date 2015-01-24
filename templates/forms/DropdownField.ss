<select $AttributesHTML>
<% loop $Options %>
	<option value="$Value.XML"<% if $Selected %> selected="selected"<% end_if %><% if $Disabled %> disabled="disabled"<% end_if %>><% if Title %>$Title.XML<% else %>&nbsp;<% end_if %></option>
<% end_loop %>
</select>
