<div class="breadcrumbs-wrapper" data-pjax-fragment="Breadcrumbs">
	<h2 id="page-title-heading">
		<% loop $Breadcrumbs %>
			<% if $Last %>
				<span class="cms-panel-link crumb last">$Title.XML</span>
			<% else %>
				<a class="cms-panel-link crumb" href="$Link">$Title.XML</a>
				<span class="sep">/</span>
			<% end_if %>
		<% end_loop %>
	</h2>
</div>
