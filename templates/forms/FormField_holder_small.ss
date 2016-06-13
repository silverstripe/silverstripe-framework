<div class="fieldholder-small">
	<% if $Title %><label class="fieldholder-small-label" <% if $ID %>aria-describedby="<% if $RightTitle %>extra-label-$ID<% end_if %>" for="$ID"<% end_if %>>$Title</label><% end_if %>
  	<div>
		<%-- TODO: add `.form-control` class to `<input ...>` --%>
		$Field
	</div>
</div>
<% if $RightTitle %>
	<p class="form__field-extra-label" <% if $ID %>id="extra-label-$ID"<% end_if %>>$RightTitle</p>
<% end_if %>
