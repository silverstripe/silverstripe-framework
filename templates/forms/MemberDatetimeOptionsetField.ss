<div $AttributesHTML>
	<% loop $Options %>
		<div class="radio $Class">
			<label>
		  		<input id="$ID" name="$Name" type="radio" value="$Value.ATT" <% if $isChecked %> checked<% end_if %><% if $isDisabled %> disabled<% end_if %> />
				$Title.XML
			</label>
			<% if $CustomName %>
				<input class="form-control customFormat cms-help cms-help-tooltip" name="$CustomName" value="$CustomValue.ATT">
				<% if $CustomPreview %>
					<span class="preview">({$CustomPreviewLabel.XML}: "{$CustomPreview.XML}")</span>
				<% end_if %>
			<% end_if %>
		</div>
	<% end_loop %>
</div>
