<div class="TreeDropdownField <% if $extraClass %> $extraClass<% end_if %><% if $ShowSearch %> searchable<% end_if %>"
     data-url-tree="$Link('tree')"
     data-title="$Title.ATT"
     data-empty-title="$EmptyTitle.ATT"
     <% if $Description %>title="$Description.ATT"<% end_if %>
     <% if $Metadata %>data-metadata="$Metadata.ATT"<% end_if %> tabindex="0">
	<input id="$ID" type="hidden" name="$Name.ATT" value="$Value.ATT" />
</div>
