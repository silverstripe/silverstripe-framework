<div class="cms-content-tools west cms-panel cms-panel-layout" id="cms-content-tools-ModelAdmin" data-expandOnClick="true" data-layout-type="border">
	<div class="cms-panel-content center">
		<h3 class="cms-panel-header"><% _t('ModelAdmin_Tools.ss.FILTER', 'Filter') %></h3>
		$SearchForm

		<% if ImportForm %>
			<h3 class="cms-panel-header"><% _t('ModelAdmin_Tools.ss.IMPORT', 'Import') %></h3>
			$ImportForm
		<% end_if %>
	</div>
	<div class="cms-panel-content-collapsed">
		<h3 class="cms-panel-header"><% _t('ModelAdmin_Tools.ss.FILTER', 'Filter') %></h3>
	</div>
</div>
