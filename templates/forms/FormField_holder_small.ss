<div class="fieldholder-small">
	<label class="fieldholder-small-label" <% if $ID %>for="$ID"<% end_if %>>$Title</label>
	$Field
	<% if $RightTitle %><label class="right fieldholder-small-label" <% if $ID %>for="$ID"<% end_if %>>$RightTitle</label><% end_if %>
</div>