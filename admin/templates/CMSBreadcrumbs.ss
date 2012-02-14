<% control Breadcrumbs %>
	<% if Last %>
		<span class="crumb">$Title.XML</span>
	<% else %>
		<a class="crumb" href="$Link">$Title.XML</a> /
	<% end_if %>
<% end_control %>