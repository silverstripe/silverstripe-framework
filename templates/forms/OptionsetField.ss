<div $AttributesHTML>
	<% loop $Options %>
		<div class="radio $Class">
			<label>
				<input id="$ID" name="$Name" type="radio" value="$Value"<% if $isChecked %> checked<% end_if %><% if $isDisabled %> disabled<% end_if %> <% if $Up.Required %>required<% end_if %> />
				$Title
			</label>
		</div>
	<% end_loop %>
</div>
