<div class="ss-uploadfield-item ss-uploadfield-addfile field">

	<h3>
		<span class="step-label">
			<span class="flyout">1</span><span class="arrow"></span>
			<span class="title"><% _t('AssetUploadField.ChooseFiles', 'Choose files') %></span>
		</span>
	</h3>



	<div class="ss-uploadfield-item-info">
		<label class="ss-uploadfield-fromcomputer ss-ui-button ss-ui-action-constructive" title="<% _t('AssetUploadField.FROMCOMPUTERINFO', 'Upload from your computer') %>" data-icon="drive-upload-large">
			<% _t('AssetUploadField.TOUPLOAD', 'Choose files to upload...') %>
			<input id="$id" name="$getName" class="$extraClass ss-uploadfield-fromcomputer-fileinput" data-config="$configString" type="file"<% if $multiple %> multiple="multiple"<% end_if %> title="<% _t('AssetUploadField.FROMCOMPUTER', 'Choose files from your computer') %>" />
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
	
	<span class="ss-uploadfield-view-allowed-extensions"> 
		<span class="description">

			<a href="#" class="toggle"><% _t('AssetAdmin.SHOWALLOWEDEXTS', 'Show allowed extensions') %></a>
			<p class="toggle-content">$Extensions</p>
		</span>	
	</span>	

	<div class="clear"><!-- --></div>
</div>

<div class="ss-uploadfield-editandorganize">
	<h3>
		<span class="step-label">
			<span class="flyout">2</span><span class="arrow"></span>
			<span class="title"><% _t('AssetUploadField.EDITANDORGANIZE', 'Edit & organize') %></span>
		</span>
	</h3>			

		<div class="ss-uploadfield-item-actions edit-all">
		<button class="ss-uploadfield-item-edit-all ss-ui-button ui-corner-all" title="<% _t('AssetUploadField.EDITINFO', 'Edit files') %>" style="display:none;">
			<% _t('AssetUploadField.EDITALL', 'Edit all') %>
				<span class="toggle-details-icon"></span>
		</button>
	</div>
	
	<ul class="ss-uploadfield-files files"></ul>
	<div class="fileOverview">
		<div class="uploadStatus message notice">
			<div class="state"><% _t('AssetUploadField.UPLOADINPROGRESS', 'Please wait… upload in progress') %></div>
			<div class="details"><% _t('AssetUploadField.TOTAL', 'Total') %>: 
				<span class="total"></span> <% _t('AssetUploadField.FILES', 'Files') %> 
				<span class="fileSize"></span> 
			</div>
		</div>		
	</div>
</div>