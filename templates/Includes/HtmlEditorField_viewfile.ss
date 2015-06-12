<div class="ss-uploadfield-item $appCategory ss-htmleditorfield-file template-upload" data-id="$File.ID" data-url="$URL">
	
	
	<div class="ss-uploadfield-item-preview">
		<% if $Width %>
			<span>$Preview.Fit(30, 40)</span>
		<% else %>
		<span class="no-preview"></span>
		<% end_if %>
	</div>
	
	<div class="ss-uploadfield-item-info">
		<label class="ss-uploadfield-item-name">
			<span class="name" title="$Name">
				$Name
			</span>
			<% if $Width %>
			<div class="ss-uploadfield-item-status ui-state-success-text" title="<% _t('UploadField.Dimensions', 'Dimensions') %>">
				{$Width} x {$Height} (px)
			</div>
			<% end_if %>

			<div class="clear"><!-- --></div> 
		</label>
		<div class="ss-uploadfield-item-actions">	
			<button data-icon="deleteLight" class="ss-uploadfield-item-cancel ss-uploadfield-item-remove" title="<% _t('UploadField.REMOVE', 'Remove') %>">
				<% _t('UploadField.REMOVE', 'Remove') %>
			</button>
			
			<div class="ss-uploadfield-item-edit edit">
				<button class="ss-uploadfield-item-edit ss-ui-button ui-corner-all" title="<% _t('UploadField.EDITINFO', 'Edit this file') %>" data-icon="pencil">
					<% _t('UploadField.EDIT', 'Edit') %>
					<span class="toggle-details">
						<span class="toggle-details-icon"></span>
					</span>
				</button>
			</div>
		</div>
		<% if $Info %><div class="info">$Info</div><% end_if %>
		<div class="details ss-uploadfield-item-editform">
			<fieldset>
				<% loop $Fields %>
					$FieldHolder
				<% end_loop %>
			</fieldset>
		</div>
	</div>
</div>
