<div class="fieldholder-small<% if $extraClass %> $extraClass<% end_if %>" id="$HolderID.ATT">
	<% if $Title %><label class="fieldholder-small-label" <% if $ID %>for="$ID"<% end_if %>>$Title</label><% end_if %>
	$Field
	<% if $RightTitle %><label class="right fieldholder-small-label" <% if $ID %>for="$ID"<% end_if %>>$RightTitle</label><% end_if %>
</div>
