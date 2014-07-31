<div id="TreeDropdownField_$ID"
     class="TreeDropdownField single<% if extraClass %> $extraClass<% end_if %><% if ShowSearch %> searchable<% end_if %>"
     data-url-tree="$Link(tree)"
     data-title="$TitleURLEncoded"
     <% if $Description %>title="$Description.ATT"<% end_if %>
     <% if $Metadata %>data-metadata="$Metadata.ATT"<% end_if %>>
	<input id="$ID" type="hidden" name="$Name.ATT" value="$Value.ATT" />
</div>
