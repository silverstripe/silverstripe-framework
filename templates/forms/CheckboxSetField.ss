<ul id="$ID" class="optionset checkboxset$extraClass">
	<% control Options %>
		<li class="$Class">
			<input id="$ID" class="checkbox" name="$Name" type="checkbox" value="$Value"<% if isChecked %> checked<% end_if %><% if isDisabled %> disabled<% end_if %>>
			<label for="$ID">$Title</label>
		</li> 
	<% end_control %>
</ul>
