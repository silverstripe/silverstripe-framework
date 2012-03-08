<div class="ss-uploadfield-item ss-uploadfield-addfile field">

	<h3>
		<span class="step-label">
			<span class="flyout">1</span><span class="arrow"></span>
			<span class="title"><% _t('AssetUploadField.ChooseFiles', 'Choose files') %></span>
		</span>
	</h3>

	<div class="ss-uploadfield-item-info">
		<label class="ss-uploadfield-fromcomputer ss-ui-button ss-ui-action-constructive" title="<% _t('AssetUploadField.FROMCOMPUTERINFO', 'Upload from your computer') %>" data-icon="drive-upload">
			<% _t('AssetUploadField.FROMCOMPUTER', 'Choose files from your computer') %>
			<input id="$id" name="$getName" class="$extraClass ss-uploadfield-fromcomputer-fileinput" data-config="$configString" type="file"<% if $multiple %> multiple="multiple"<% end_if %>> title="<% _t('AssetUploadField.FROMCOMPUTER', 'Choose files from your computer') %>" />
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

<div class="ss-uploadfield-editandorganize">
	<h3>
		<span class="step-label">
			<span class="flyout">2</span><span class="arrow"></span>
			<span class="title"><% _t('AssetUploadField.EDITANDORGANIZE', 'Edit & organize') %></span>
		</span>
	</h3>
	<ul class="ss-uploadfield-files files"></ul>
</div>