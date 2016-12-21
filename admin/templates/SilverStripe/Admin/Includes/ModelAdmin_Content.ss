<div class="cms-content flexbox-area-grow cms-tabset center $BaseCSSClasses" data-layout-type="border" data-pjax-fragment="Content">

	<div class="cms-content-header north">
		<div class="cms-content-header-info">
			<div class="breadcrumbs-wrapper">
				<h2 id="page-title-heading">
					<span class="cms-panel-link crumb last">
						<% if $SectionTitle %>
							$SectionTitle
						<% else %>
							<%t ModelAdmin.Title 'Data Models' %>
						<% end_if %>
					</span>
				</h2>
			</div>
		</div>

		<div class="cms-content-header-tabs cms-tabset-nav-primary ss-ui-tabs-nav">
            <% if $SearchForm %>
			    <button id="filters-button" class="btn btn-secondary btn--icon-large font-icon-search no-text" title="<%t CMSPagesController_Tools_ss.FILTER 'Filter' %>"></button>
            <% end_if %>
			<ul class="cms-tabset-nav-primary">
				<% loop $ManagedModelTabs %>
				<li class="tab-$ClassName $LinkOrCurrent<% if $LinkOrCurrent == 'current' %> ui-tabs-active<% end_if %>">
					<a href="$Link" class="cms-panel-link" title="Form_EditForm">$Title</a>
				</li>
				<% end_loop %>
			</ul>
		</div>
	</div>

	<div class="cms-content-fields center ui-widget-content cms-panel-padded fill-height" data-layout-type="border">
		$Tools

		<div class="cms-content-view">
			$EditForm
		</div>

        <% if ImportForm %>
            <div id="{$EditForm.HTMLID}_ImportModal" class="modal fade grid-field-import" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content"><div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                    </div>

                    <div class="modal-body">
                        $ImportForm
                    </div>
                </div>
            </div>
        <% end_if %>
	</div>

</div>
