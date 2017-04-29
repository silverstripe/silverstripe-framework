<span id="$ID" <% if $extraClass %>class="$extraClass"<% end_if %>>
	$Value
</span>
<% if $IncludeHiddenField %>
	<input $getAttributesHTML("id", "type") id="hidden-{$ID}" type="hidden" />
<% end_if %>
