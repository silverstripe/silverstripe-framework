<div class="cms-navigator">

	<% include LeftAndMain_ViewModeSelector SelectID="preview-mode-dropdown-in-preview" %>
	
    <span id="preview-size-dropdown" class="preview-size-selector preview-selector field dropdown">
		<select title="<% _t('SilverStripeNavigator.ViewDeviceWidth', 'Select a preview width') %>" id="preview-size-dropdown-select" class="preview-dropdown dropdown nolabel" autocomplete="off" name="Action">
			<option data-icon="icon-auto" data-description="<% _t('SilverStripeNavigator.Responsive', 'Responsive') %>" class="icon-auto icon-view first" value="auto">
				<% _t('SilverStripeNavigator.Auto', 'Auto') %>
			</option>
			<option data-icon="icon-desktop" data-description="1024px <% _t('SilverStripeNavigator.Width', 'width') %>" class="icon-desktop icon-view" value="desktop">
				<% _t('SilverStripeNavigator.Desktop', 'Desktop') %>
			</option>
			<option data-icon="icon-tablet" data-description="800px <% _t('SilverStripeNavigator.Width', 'width') %>" class="icon-tablet icon-view" value="tablet">
				<% _t('SilverStripeNavigator.Tablet', 'Tablet') %>
			</option>
			<option data-icon="icon-mobile" data-description="400px <% _t('SilverStripeNavigator.Width', 'width') %>" class="icon-mobile icon-view last" value="mobile">
				<% _t('SilverStripeNavigator.Mobile', 'Mobile') %>
			</option>
		</select>
	</span>

	<% if $Items %>
		<% if $Items.Count < 5 %>
			<fieldset id="preview-states" class="cms-preview-states switch-states size_{$Items.Count}"> 			
				<div class="switch">
					<% loop $Items %>					
						<input id="$Title" data-name="$Name" class="state-name $FirstLast" data-link="$Link" name="view" type="radio" <% if $isActive %>checked<% end_if %>>
						<label for="$Title"<% if $isActive %> class="active"<% end_if %>><span>$Title</span></label>
					<% end_loop %>
					<span class="slide-button"></span>
				</div>
			</fieldset>
		<% else %>
			<span id="preview-state-dropdown" class="cms-preview-states field dropdown">
				<select title="<% _t('SilverStripeNavigator.PreviewState', 'Preview State') %>" id="preview-states" class="preview-state dropdown nolabel" autocomplete="off" name="preview-state">
					<% loop $Items %>	
					<option name="$Name" data-name="$Name" data-link="$Link" class="state-name $FirstLast" value="$Link" <% if $isActive %>selected<% end_if %>>
						$Title
					</option>
					<% end_loop %>	
				</select>
			</span>
		<% end_if %>
	<% end_if %>
</div>
