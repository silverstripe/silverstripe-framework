<div id="$id">
	<%-- Tab nav is rendered in CMSEditForm.ss --%>

	<% loop Tabs %>
	<div $AttributesHTML>
	<% if Tabs %>
		$FieldHolder
	<% else %>
		<% loop Fields %>
		$FieldHolder
		<% end_loop %>
	<% end_if %>
	</div>
	<% end_loop %>
</div>
