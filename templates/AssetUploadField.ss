<div class="ss-uploadfield-item ss-uploadfield-addfile field">

	<h3>
		<span class="step-label">
			<span class="flyout">1</span><span class="arrow"></span>
			<span class="title"><%t AssetUploadField.ChooseFiles 'Choose files' %></span>
		</span>
	</h3>



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
			<%t AssetUploadField.DROPAREA 'Drop Area' %>
			<span><%t AssetUploadField.DRAGFILESHERE 'Drag files here' %></span>
		</div>
	</div>

	<span class="ss-uploadfield-view-allowed-extensions">
		<span class="description">

			<a href="#" class="toggle"><%t AssetAdmin.SHOWALLOWEDEXTS 'Show allowed extensions' %></a>
			<p class="toggle-content">$Extensions</p>
		</span>
	</span>

	<div class="clear"><!-- --></div>
</div>

<div class="ss-uploadfield-editandorganize">
	<h3>
		<span class="step-label">
			<span class="flyout">2</span><span class="arrow"></span>
			<span class="title"><%t AssetUploadField.EDITANDORGANIZE 'Edit & organize' %></span>
		</span>
	</h3>

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
