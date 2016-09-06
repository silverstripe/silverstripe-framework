<ul $AttributesHTML>
	<% loop $Options %>
		<li class="$Class">
			<input id="$ID" class="radio" name="$Name" type="radio" value="$Value.ATT" <% if $isChecked %> checked<% end_if %><% if $isDisabled %> disabled<% end_if %> />
			<label for="$ID">$Title.XML</label>
			<% if $CustomName %>
				<input class="customFormat cms-help cms-help-tooltip" name="$CustomName" value="$CustomValue.ATT">
				<% if $CustomPreview %>
					<span class="preview">({$CustomPreviewLabel.XML}: "{$CustomPreview.XML}")</span>
				<% end_if %>
			<% end_if %>
		</li>
	<% end_loop %>
</ul>
