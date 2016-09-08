<div class="ss-uploadfield-item ss-uploadfield-addfile field ss-uploadfield">

	<div class="ss-uploadfield-item-info">
		<label class="btn btn-secondary font-icon-upload btn--icon-xl ss-uploadfield-fromcomputer" title="<%t AssetUploadField.FROMCOMPUTERINFO 'Upload from your computer' %>">
			<%t AssetUploadField.TOUPLOAD 'Upload files' %>
			<input id="$id" name="$getName" class="$extraClass ss-uploadfield-fromcomputer-fileinput" data-config="$configString" type="file"<% if $multiple %> multiple="multiple"<% end_if %> title="<%t AssetUploadField.FROMCOMPUTER 'Choose files from your computer' %>" />
		</label>
		<a class="btn btn-secondary font-icon-link btn--icon-large upload-url"><%t AssetUploadField.INSERTURL 'Insert from URL' %></a>
	</div>
</div>

<div class="ss-uploadfield-editandorganize">
	<ul class="ss-uploadfield-files files"></ul>
</div>
