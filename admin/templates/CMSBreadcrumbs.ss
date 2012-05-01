<div class="breadcrumbs-wrapper">
	<% control Breadcrumbs %>
		<% if Last %>
			<span class="cms-panel-link crumb">$Title.XML<span class="show-ellipsis">...</span></span>
		<% else %>
			<a class="cms-panel-link crumb" href="$Link" title="$Title.XML">$Title.XML<span class="show-ellipsis">...</span></a>/
		<% end_if %>
	<% end_control %>
</div>