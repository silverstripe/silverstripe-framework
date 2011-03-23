<div id="treepanes">
	<h3>
		<a href="#"><% _t('SECGROUPS','Security Groups') %></a>
	</h3>
	
	<div>
		<div id="TreeActions">

			<ul>
				<li>
					<a href="#TreeActions-create" id="TreeActions-create-btn">
						<% _t('CREATE','Create',PR_HIGH) %>
					</a>
				</li>
				<li>
					<a href="#TreeActions-batchactions" id="batchactions">
						<% _t('BATCHACTIONS','Batch Actions',PR_HIGH) %>
					</a>
				</li>
			</ul>

			<div id="TreeActions-create">
				$AddForm
			</div>

			<div id="TreeActions-batchactions">
				$BatchActionsForm
			</div>

		</div>
		
		<div class="checkboxAboveTree">
			<input type="checkbox" id="sortitems" />
			<label for="sortitems">
				<% _t('ENABLEDRAGGING','Allow drag &amp; drop reordering', PR_HIGH) %>
			</label>
		</div>

		<div id="sitetree_ul" data-url-tree="$Link(getsubtree)" data-url-savetreenode="$Link(savetreenode)" class="jstree jstree-apple">
			$SiteTreeAsUL
		</div>
		
	</div>
	
</div>