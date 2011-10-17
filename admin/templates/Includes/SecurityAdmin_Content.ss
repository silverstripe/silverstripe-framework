<div class="cms-content center $BaseCSSClasses" data-layout="{type: 'border'}">

	<div class="cms-content-header north">
		<div>
			<h2><% _t('SECGROUPS','Security Groups') %></h2>
		</div>
	</div>


	<div class="cms-content-tools west">

		<div id="treepanes">

			<div>
				$AddForm

				<div class="checkboxAboveTree">
					<input type="checkbox" id="sortitems" />
					<label for="sortitems">
						<% _t('ENABLEDRAGGING','Allow drag &amp; drop reordering', PR_HIGH) %>
					</label>
				</div>

				<div data-url-tree="$Link(getsubtree)" data-url-savetreenode="$Link(savetreenode)" class="cms-tree jstree jstree-apple">
					$SiteTreeAsUL
				</div>

			</div>

		</div>
		
	</div>

	<div class="cms-content-fields center ui-widget-content">
		$EditForm
	</div>
	
</div>