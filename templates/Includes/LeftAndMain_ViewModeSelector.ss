<span id="$SelectID" class="preview-mode-selector preview-selector field dropdown">
	<select title="<%t SilverStripeNavigator.ChangeViewMode 'Change view mode' %>" id="$SelectID-select" class="preview-dropdown dropdown nolabel no-change-track" autocomplete="off" name="Action">

		<option data-icon="icon-split" class="icon-split icon-view first" value="split"><%t SilverStripeNavigator.SplitView 'Split mode' %></option>
		<option data-icon="icon-preview" class="icon-preview icon-view" value="preview"><%t SilverStripeNavigator.PreviewView 'Preview mode' %></option>
		<option data-icon="icon-edit" class="icon-edit icon-view last" value="content"><%t SilverStripeNavigator.EditView 'Edit mode' %></option>
		<!-- Dual window not implemented yet -->
		<!--
			<option data-icon="icon-window" class="icon-window icon-view last" value="window"><%t SilverStripeNavigator.DualWindowView 'Dual Window' %></option>
		-->
	</select>
</span>
