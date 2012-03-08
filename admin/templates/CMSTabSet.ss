<div id="$id">
	
	<%-- Tab nav is rendered in CMSEditForm.ss --%>
	
	<% control Tabs %>
	<div class="tab" id="$id">
	<% if Tabs %>
		$FieldHolder
	<% else %>
		<% control Fields %>
		$FieldHolder
		<% end_control %>
	<% end_if %>
	</div>
	<% end_control %>
</div>