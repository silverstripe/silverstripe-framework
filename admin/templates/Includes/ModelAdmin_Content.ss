<div class="cms-content center $BaseCSSClasses" data-layout-type="border">

	<div class="cms-content-header north">
		<div>
			<h2>
				<% if SectionTitle %>
					$SectionTitle
				<% else %>
					<% _t('ModelAdmin.Title', 'Data Models') %>
				<% end_if %>
			</h2>

			<div class="cms-content-header-tabs ss-ui-tabs-nav">
				<ul>
				<% control ManagedModelTabs %>
					<li class="tab-$ClassName $LinkOrCurrent">
						<a href="$Link" class="cms-panel-link">$Title</a>
					</li>
				<% end_control %>
				</ul>
			</div>

		</div>
	</div>

	<div class="cms-content-fields center ui-widget-content" data-layout-type="border">
		$Tools
		$EditForm
	</div>
	
</div>