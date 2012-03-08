<%-- Renders a CheckboxField with $multiple=true as a select element which can save into relations.--%>
<%-- TODO Make relation saving available on ListboxField --%>
<select id="$ID" class="$extraClass" name="$Name[]" multiple="true">
	<% if Options.Count %>
		<% control Options %>
			<option class="$Class" value="$Value"<% if isChecked %> selected="selected"<% end_if %>>
				$Title
			</option> 
		<% end_control %>
	<% end_if %>
</select>