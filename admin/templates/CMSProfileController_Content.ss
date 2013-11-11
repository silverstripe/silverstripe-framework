<div id="settings-controller-cms-content" class="cms-content center cms-tabset $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content">

	<div class="cms-content-header north">
		<% with $EditForm %>
			<div class="cms-content-header-info">				
				<% with $Controller %>
					<% include CMSBreadcrumbs %>
				<% end_with %>				
			</div>
			<% if $Fields.hasTabset %>
				<% with $Fields.fieldByName('Root') %>
				<div class="cms-content-header-tabs">
					<ul class="cms-tabset-nav-primary">
					<% loop $Tabs %>
						<li<% if $extraClass %> class="$extraClass"<% end_if %>><a href="#$id">$Title</a></li>
					<% end_loop %>
					</ul>
				</div>
				<% end_with %>
			<% end_if %>
		<% end_with %>
	</div>

	$EditForm

</div>
