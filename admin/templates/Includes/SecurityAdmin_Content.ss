<div class="cms-content center $BaseCSSClasses" data-layout="{type: 'border'}">
	<div class="cms-content-tools west">
		<div class="cms-content-header north">
			<div>
				<h2><% _t('SECGROUPS','Security Groups') %></h2>
			</div>
		</div>

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

	<% with EditForm %>
		<div class="cms-content-fields center ui-widget-content ss-tabset">
	
			<% if IncludeFormTag %>
			<form $FormAttributes data-layout="{type: 'border'}">
			<% end_if %>
		
			<div class="cms-content-header north">
				<% if Fields.hasTabset %>
					<% with Fields.fieldByName('Root') %>
						<div class="cms-content-header-tabs">
							<ul>
							<% control Tabs %>
								<li><a href="#$id">$Title</a></li>
							<% end_control %>
							</ul>
						</div>
					<% end_with %>
				<% end_if %>
			</div>

			<div class="cms-content-fields center">
				<% if Message %>
				<p id="{$FormName}_error" class="message $MessageType">$Message</p>
				<% else %>
				<p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
				<% end_if %>

				<fieldset>
					<% if Legend %><legend>$Legend</legend><% end_if %> 
					<% control Fields %>
						$FieldHolder
					<% end_control %>
					<div class="clear"><!-- --></div>
				</fieldset>
			</div>
	
			<div class="cms-content-actions south">
				<% if Actions %>
				<div class="Actions">
					<% control Actions %>
						$Field
					<% end_control %>
				</div>
				<% end_if %>
			</div>
	
			<% if IncludeFormTag %>
			</form>
			<% end_if %>
		</div>
	<% end_with %>
</div>