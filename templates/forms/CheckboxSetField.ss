<div $AttributesHTML>
	<% if $Options.Count %>
		<% loop $Options %>
			<div class="$Class">
				<label>
					<input id="$ID" class="checkbox" name="$Name" type="checkbox" value="$Value"<% if $isChecked %> checked="checked"<% end_if %><% if $isDisabled %> disabled="disabled"<% end_if %> />
					$Title
				</label>
			</div>
		<% end_loop %>
	<% else %>
		<p>No options available</p>
	<% end_if %>
</div>
