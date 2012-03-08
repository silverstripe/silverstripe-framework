<div class="cms-content-tools west cms-panel cms-panel-layout" data-expandOnClick="true" data-layout-type="border">	
	<div class="cms-panel-content center">
		<h3 class="cms-panel-header"><% _t('Filter', 'Filter') %></h3>
	
		<div id="SearchForm_holder" class="leftbottom ss-tabset">		
			<% if SearchClassSelector = tabs %>
				<ul>
					<% control ModelForms %>
						 <li class="$FirstLast"><a id="tab-ModelAdmin_$Title.HTMLATT" href="#Form_$ClassName">$Title</a></li>
					<% end_control %>
				</ul>
			<% end_if %>

			<% if SearchClassSelector = dropdown %>
				<div id="ModelClassSelector" class="ui-widget-container">
					Search for:
					<select>
						<% control ModelForms %>
							<option value="Form_$ClassName">$Title</option>
						<% end_control %>
					</select>
				</div>
			<% end_if %>

			<% control ModelForms %>
				<div class="tab" id="Form_$ClassName">
					$Content
				</div>
			<% end_control %>
		</div>
	</div>
	
</div>