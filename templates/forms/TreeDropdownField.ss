<div id="TreeDropdownField_$ID"
     class="TreeDropdownField single<% if extraClass %> $extraClass<% end_if %><% if ShowSearch %> searchable<% end_if %>"
     data-url-tree="$Link(tree)"
     data-title="$TitleURLEncoded"
     <% if $Description %>title="$Description"<% end_if %>
     <% if $Metadata %>data-metadata="$Metadata"<% end_if %>>
	<input id="$ID" type="hidden" name="$Name" value="$Value" />
</div>
