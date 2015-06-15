<div class="cms-content cms-tabset center $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content">

	<div class="cms-content-header north">
		<div class="cms-content-header-info">
			<h2>
				<% include CMSSectionIcon %>
				<% if $SectionTitle %>
					$SectionTitle
				<% else %>
					<% _t('ModelAdmin.Title', 'Data Models') %>
				<% end_if %>
			</h2>
		</div>

		<div class="cms-content-header-tabs cms-tabset-nav-primary ss-ui-tabs-nav">
			<button id="filters-button" class="icon-button font-icon-search" title="<% _t('CMSPagesController_Tools_ss.FILTER', 'Filter') %>"></button>
			<ul class="cms-tabset-nav-primary">
				<% loop $ManagedModelTabs %>
				<li class="tab-$ClassName $LinkOrCurrent<% if $LinkOrCurrent == 'current' %> ui-tabs-active<% end_if %>">
					<a href="$Link" class="cms-panel-link" title="Form_EditForm">$Title</a>
				</li>
				<% end_loop %>
			</ul>
		</div>
	</div>

	<div class="cms-content-fields center ui-widget-content cms-panel-padded" data-layout-type="border">
		$Tools

		<div class="cms-content-view">
			$EditForm
		</div>
	</div>
	
</div>
