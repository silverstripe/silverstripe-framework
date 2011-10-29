<div class="cms-content center $BaseCSSClasses" data-layout="{type: 'border'}">

	<div class="cms-content-header north">
		<div>
			<h2><% _t('ModelAdmin.Title', 'My Model') %></h2>
		</div>
	</div>

	<div class="cms-content-tools west cms-panel cms-panel-layout" data-expandOnClick="true" data-layout="{type: 'border'}">
		
		<div class="north">
			<h3 class="cms-panel-header">Filter</h3>
		<div>
			
		<div class="cms-panel-content center">
			<div id="SearchForm_holder" class="leftbottom ss-tabset">		
				<% if SearchClassSelector = tabs %>
					<ul>
						<% control ModelForms %>
							 <li class="$FirstLast"><a id="tab-ModelAdmin_$Title.HTMLATT" href="#{$Form.Name}_$ClassName">$Title</a></li>
						<% end_control %>
					</ul>
				<% end_if %>

				<% if SearchClassSelector = dropdown %>
					<div id="ModelClassSelector" class="ui-widget-container">
						Search for:
						<select>
							<% control ModelForms %>
								<option value="{$Form.Name}_$ClassName">$Title</option>
							<% end_control %>
						</select>
					</div>
				<% end_if %>

				<% control ModelForms %>
					<div class="tab" id="{$Form.Name}_$ClassName">
						$Content
					</div>
				<% end_control %>
			</div>
		</div>
		
	</div>

	<div class="cms-content-fields center ui-widget-content">
		$EditForm
	</div>
	
</div>