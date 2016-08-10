<%-- Exclude ".ss-tabset" class to avoid inheriting behaviour --%>
<%-- The ".cms-tabset" class needs to be manually applied to a container elment, --%>
<%-- above the level where the tab navigation is placed. --%>
<%-- Tab navigation is rendered through various templates, --%>
<%-- e.g. through LeftAndMain_EditForm.ss. --%>

<div $AttributesHTML>
	<% loop $Tabs %>
		<% if $Tabs %>
			$FieldHolder
		<% else %>
			<div $AttributesHTML>
				<% loop $Fields %>
					$FieldHolder
				<% end_loop %>
			</div>
		<% end_if %>
	<% end_loop %>
</div>
