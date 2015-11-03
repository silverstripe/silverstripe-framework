<div class="ss-uploadfield-item ss-uploadfield-addfile field">

	<div class="ss-uploadfield-item-info">
		<label class="ss-uploadfield-fromcomputer font-icon-upload ss-ui-button ss-ui-action-constructive" title="<%t AssetUploadField.FROMCOMPUTERINFO 'Upload from your computer' %>">
			<%t AssetUploadField.TOUPLOAD 'Upload files' %>
			<input id="$id" name="$getName" class="$extraClass ss-uploadfield-fromcomputer-fileinput" data-config="$configString" type="file"<% if $multiple %> multiple="multiple"<% end_if %> title="<%t AssetUploadField.FROMCOMPUTER 'Choose files from your computer' %>" />
		</label>
	</div>

	<span class="ss-uploadfield-view-allowed-extensions"> 
		<span class="description">

			<a href="#" class="font-icon-info-circled toggle"><%t AssetAdmin.SHOWALLOWEDEXTS 'Show allowed extensions' %></a>
			<div class="toggle-content">
				<h4>Allowed file upload extensions</h4>
				<p>$Extensions</p>
			</div>
		</span>	
	</span>
</div>

<div class="ss-uploadfield-editandorganize">
	<div class="ss-uploadfield-item-actions edit-all">
		<button type="button" class="ss-uploadfield-item-edit-all ss-ui-button ui-corner-all" title="<%t AssetUploadField.EDITINFO 'Edit files' %>" style="display:none;">
			<%t AssetUploadField.EDITALL 'Edit all' %>
				<span class="toggle-details-icon"></span>
		</button>
	</div>
	
	<ul class="ss-uploadfield-files files"></ul>

	<div class="fileOverview">
		<div class="uploadStatus message notice">
			<div class="state"><%t AssetUploadField.UPLOADINPROGRESS 'Please wait… upload in progress' %></div>
			<div class="details"><%t AssetUploadField.TOTAL 'Total' %>: 
				<span class="total"></span> <%t AssetUploadField.FILES 'Files' %> 
				<span class="fileSize"></span> 
			</div>
		</div>		
	</div>

</div>