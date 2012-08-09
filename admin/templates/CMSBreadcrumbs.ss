<div class="breadcrumbs-wrapper" data-pjax-fragment="Breadcrumbs">

	<% if ToplevelController %>
		<span class="section-icon icon icon-16 icon-{$ToplevelController.MenuCurrentItem.Code.LowerCase}"></span>
	<% else_if Controller %>
		<span class="section-icon icon icon-16 icon-{$Controller.MenuCurrentItem.Code.LowerCase}"></span>
	<% else %>
		<span class="section-icon icon icon-16 icon-{$MenuCurrentItem.Code.LowerCase}"></span>
	<% end_if %>

	<% loop Breadcrumbs %>
		<% if Last %>
			<span class="cms-panel-link crumb last">$Title.XML</span>
		<% else %>
			<a class="cms-panel-link crumb" href="$Link">$Title.XML</a>
			<span class="sep">/</span>
		<% end_if %>
	<% end_loop %>
</div>
