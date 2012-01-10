<div class="cms-content center $BaseCSSClasses" data-layout="{type: 'border'}">
	<div class="cms-content-tools west">
		<div class="cms-content-header north">
			<div>
				<h2><% _t('SECGROUPS','Security Groups') %></h2>
			</div>
		</div>

		$AddForm

		<div data-url-tree="$Link(getsubtree)" data-url-savetreenode="$Link(savetreenode)" class="cms-tree draggable jstree jstree-apple">
			$SiteTreeAsUL
		</div>

	</div>

	$EditForm	
	
</div>