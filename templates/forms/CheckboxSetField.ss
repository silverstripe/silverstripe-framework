<ul id="$ID" class="$extraClass">
	<% if $Options.Count %>
		<% loop $Options %>
			<li class="$Class">
				<input id="$ID" class="checkbox" name="$Name.ATT" type="checkbox" value="$Value.ATT"<% if $isChecked %> checked="checked"<% end_if %><% if $isDisabled %> disabled="disabled"<% end_if %> />
				<label for="$ID">$Title.XML</label>
			</li> 
		<% end_loop %>
	<% else %>
		<li>No options available</li>
	<% end_if %>
</ul>
