<div class="ss-uploadfield-item ss-uploadfield-addfile field ss-uploadfield">

	<h4>
		<span class="step-label">
			<span class="flyout">1</span><span class="arrow"></span>
			<span class="title"><%t AssetUploadField.ChooseFiles 'Choose files' %></span>
		</span>
	</h4>

	<div class="ss-uploadfield-item-info">
		<label class="ss-uploadfield-fromcomputer ss-ui-button ss-ui-action-constructive" title="<%t AssetUploadField.FROMCOMPUTERINFO 'Upload from your computer' %>" data-icon="drive-upload-large">
			<%t AssetUploadField.TOUPLOAD 'Choose files to upload...' %>
			<input id="$id" name="$getName" class="$extraClass ss-uploadfield-fromcomputer-fileinput" data-config="$configString" type="file"<% if $multiple %> multiple="multiple"<% end_if %> title="<%t AssetUploadField.FROMCOMPUTER 'Choose files from your computer' %>" />
		</label>

		<div class="clear"><!-- --></div>
	</div>
	<div class="ss-uploadfield-item-uploador">
		<%t AssetUploadField.UPLOADOR 'OR' %>
	</div>
	<div class="ss-uploadfield-item-preview ss-uploadfield-dropzone">
		<div>
			<span><%t AssetUploadField.DRAGFILESHERE 'Drag files here' %></span>
		</div>
	</div>
	<div class="clear"><!-- --></div>
</div>

<div class="ss-uploadfield-editandorganize">
	<ul class="ss-uploadfield-files files"></ul>
</div>
