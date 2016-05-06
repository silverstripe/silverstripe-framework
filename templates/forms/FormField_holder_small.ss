<div class="fieldholder-small">
	<div class="row form-group">
		<% if $Title %><label class="col-sm-2 fieldholder-small-label" <% if $ID %>for="$ID"<% end_if %>>$Title</label><% end_if %>
	  	<div class="col-sm-10<% if $Title %> col-sm-push-2<% end_if %>">
			<!-- TODO: add `.form-control` class to `<input ...>` -->
			$Field
		</div>
	</div>
	<% if $RightTitle %>
		<div class="col-sm-10- col-sm-push-2">
			<p class="text-muted fieldholder-small-label" <% if $ID %>for="$ID"<% end_if %>>$RightTitle</p>
		</div>
	<% end_if %>
</div>
