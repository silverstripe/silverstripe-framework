<div class="fieldholder-small">
	<% if $RightTitle %>
		<label class="right fieldholder-small-label" <% if ID %>for="$ID"<% end_if %>>$RightTitle</label>
	<% else_if $LeftTitle %>
		<label class="left fieldholder-small-label" <% if ID %>for="$ID"<% end_if %>>$LeftTitle</label>
	<% else_if $Title %>
		<label class="fieldholder-small-label" <% if ID %>for="$ID"<% end_if %>>$Title</label>
	<% end_if %>
	
	$Field
</div>