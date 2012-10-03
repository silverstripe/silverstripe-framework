<div $AttributesHTML>
	<%-- Tab nav is rendered in CMSEditForm.ss --%>

	<% loop Tabs %>
		<% if Tabs %>
			$FieldHolder
		<% else %>
			<div $AttributesHTML>
				<% loop Fields %>
					$FieldHolder
				<% end_loop %>
			</div>
		<% end_if %>
	<% end_loop %>
</div>
