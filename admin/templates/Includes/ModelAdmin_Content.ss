<div class="cms-content center $BaseCSSClasses" data-layout-type="border">

	<div class="cms-content-header north">
		<div><h2>
			<% if SectionTitle %>
				$SectionTitle
			<% else %>
				<% _t('ModelAdmin.Title', 'Data Models') %>
			<% end_if %>
		</h2></div>
	</div>

	$Tools

	<div class="cms-content-fields center ui-widget-content">
		$EditForm
	</div>
	
</div>