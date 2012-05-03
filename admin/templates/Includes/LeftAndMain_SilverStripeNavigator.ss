<div class="cms-navigator">
	<a href="#" class="ss-ui-button cms-preview-toggle-link" data-icon="preview">
		&laquo; <% _t('SilverStripeNavigator.Edit', 'Edit') %>
	</a>
	<ul class="cms-preview-states">
		<% control Items %>
			<li class="<% if isActive %> active<% end_if %>">$HTML
				<% if WatermarkHTML %><span class="cms-preview-watermark">$WatermarkHTML</span><% end_if %>
			</li>
		<% end_control %>
	</ul>
	<a href="$Record.Link" target="_blank" class="cms-preview-popup-link">
		<% _t('SilverStripeNavigator.OpenNewWindow', 'Open in new window') %>
	</a>
</div>