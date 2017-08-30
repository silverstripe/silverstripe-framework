<div
	class="TreeDropdownField<% if $extraClass %> $extraClass<% end_if %><% if $ShowSearch %> searchable<% end_if %>"
	$AttributesHTML('class')
	<% if $Metadata %>data-metadata="$Metadata.ATT"<% end_if %>
>
	<input id="$ID" type="hidden" name="$Name.ATT" value="$Value.ATT" />
</div>
