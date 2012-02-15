<div class="ss-uploadfield-item ss-uploadfield-addfile">
	<div class="ss-uploadfield-item-info">
		<label class="ss-uploadfield-fromcomputer ss-ui-button ui-corner-all" title="<% _t('AssetUploadField.FROMCOMPUTERINFO', 'Upload from your computer') %>" for="$id">
			<input id="$id" name="$getName" class="$extraClass" data-config="$configString" type="file"<% if $multiple %> multiple="multiple"<% end_if %><% if $TabIndex %> tabindex="$TabIndex"<% end_if %> />
			<span><% _t('AssetUploadField.FROMCOMPUTER', 'Choose files from your computer') %></span>
		</label>
		<div class="clear"><!-- --></div>
	</div>
	<div class="ss-uploadfield-item-uploador">
		<% _t('AssetUploadField.UPLOADOR', 'OR') %>
	</div>
	<div class="ss-uploadfield-item-preview ss-uploadfield-dropzone">
		<div>
			<% _t('AssetUploadField.DROPAREA', 'Drop Area') %>
			<span><% _t('AssetUploadField.DRAGFILESHERE', 'Drag files here') %></span>
		</div>
	</div>
	<div class="clear"><!-- --></div>
</div>
<h3 class="ss-uploadfield-editandorganize"><% _t('AssetUploadField.EDITANDORGANIZE', 'Edit & organize') %></h3>
<ul class="ss-uploadfield-files files"></ul>