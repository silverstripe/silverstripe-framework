<div class="cms-navigator">
	<a href="#" class="ss-ui-button cms-preview-toggle-link" data-icon="preview">
		&laquo; <% _t('SilverStripeNavigator.Edit', 'Edit') %>
	</a>
	<ul class="cms-preview-states">
		<% loop Items %>
			<li class="<% if isActive %> active<% end_if %>">$HTML
				<% if Watermark %><span class="cms-preview-watermark">$Watermark</span><% end_if %>
			</li>
		<% end_loop %>
	</ul>
</div>
