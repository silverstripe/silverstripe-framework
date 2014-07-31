<div class="fieldholder-small">
	<% if $Title %><label class="fieldholder-small-label" <% if $ID %>for="$ID"<% end_if %>>$Title.XML</label><% end_if %>
	$Field
	<% if $RightTitle %><label class="right fieldholder-small-label" <% if $ID %>for="$ID"<% end_if %>>$RightTitle.XML</label><% end_if %>
</div>
