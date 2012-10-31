<%-- Exclude ".ss-tabset" class to avoid inheriting behaviour --%>
<%-- The ".cms-tabset" class needs to be manually applied to a container elment, --%>
<%-- above the level where the tab navigation is placed. --%>
<%-- Tab navigation is rendered through various templates, --%>
<%-- e.g. through LeftAndMain_EditForm.ss. --%>

<div $AttributesHTML>
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
