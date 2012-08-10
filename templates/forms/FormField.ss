<% if isReadonly %>
	<span id="$ID"
	      <% if extraClass %>class="$extraClass"<% end_if %>
	      <% if $Description %>title="$Description"<% end_if %>>
		$Value
	</span>
<% else %>
	<input $AttributesHTML />
<% end_if %>
