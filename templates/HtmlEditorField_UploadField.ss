<div class="ss-uploadfield-item ss-uploadfield-addfile field ss-uploadfield">

	<div class="ss-uploadfield-item-info">
		<label class="ss-uploadfield-fromcomputer font-icon-upload ss-ui-button ss-ui-action-constructive" title="<%t AssetUploadField.FROMCOMPUTERINFO 'Upload from your computer' %>">
			<%t AssetUploadField.TOUPLOAD 'Upload files' %>
			<input id="$id" name="$getName" class="$extraClass ss-uploadfield-fromcomputer-fileinput" data-config="$configString" type="file"<% if $multiple %> multiple="multiple"<% end_if %> title="<%t AssetUploadField.FROMCOMPUTER 'Choose files from your computer' %>" />
		</label>
		<span><%t AssetUploadField.UPLOADSUBTEXTPAGE 'Drag & drop files or ' %><a class="upload-url">add by URL</a></span>

		<div class="clear"><!-- --></div>
	</div>

	<div class="clear"><!-- --></div>
</div>

<div class="ss-uploadfield-editandorganize">
	<ul class="ss-uploadfield-files files"></ul>
</div>