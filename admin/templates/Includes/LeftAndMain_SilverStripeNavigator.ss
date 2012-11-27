<div class="cms-navigator">

	<% include LeftAndMain_ViewModeSelector SelectID="preview-mode-dropdown-in-preview" %>
	
    <span id="preview-size-dropdown" class="preview-size-selector preview-selector field dropdown">
		<select title="<% _t('SilverStripeNavigator.ViewDeviceWidth', 'View at device width') %>" id="preview-size-dropdown-select" class="preview-dropdown dropdown nolabel" autocomplete="off" name="Action">
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

	<div class="cms-preview-states switch-states">
		<input type="checkbox" name="cms-preview" class="state cms-preview" id="cms-preview-state" checked>
		<label for="cms-preview-state">
			<span class="switch-options">
				<% loop Items %>
				$Items.count
				<a href="$Link" class="$FirstLast <% if isActive %> active<% end_if %>">$Title</a>
				<% end_loop %>
			</span>
			<span class="switch"></span>
		</label>
	</div>

</div>
