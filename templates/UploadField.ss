<ul class="ss-uploadfield-files files">
	<% if $Items %>
		<% loop $Items %>
			<li class="ss-uploadfield-item template-download" data-fileid="$ID">
				<div class="ss-uploadfield-item-preview preview"><span>
					<img alt="$hasRelation" src="$UploadFieldThumbnailURL" />
				</span></div>
				<div class="ss-uploadfield-item-info">
					<label class="ss-uploadfield-item-name">
						<b>{$Title}.{$Extension}</b>
						<span>$Size</span>
						<div class="clear"><!-- --></div>
					</label>
					<div class="ss-uploadfield-item-actions">
						<% if Top.isDisabled || Top.isReadonly %>
						<% else %>
							$UploadFieldFileButtons
						<% end_if %>
					</div>
				</div>
				<div class="ss-uploadfield-item-editform loading includeParent">
					<iframe frameborder="0" src="$UploadFieldEditLink"></iframe>
				</div>
			</li>
		<% end_loop %>
	<% end_if %>
</ul>
<% if isDisabled || isReadonly %>
	<% if isSaveable %>
	<% else %>
		<div class="ss-uploadfield-item">
			<em><% _t('FileIFrameField.ATTACHONCESAVED2', 'Files can be attached once you have saved the record for the first time.') %></em>
		</div>
	<% end_if %>
<% else %>
	<div class="ss-uploadfield-item ss-uploadfield-addfile<% if $Items && $displayInput %> borderTop<% end_if %>" <% if not $displayInput %>style="display: none;"<% end_if %>>
		<div class="ss-uploadfield-item-preview ss-uploadfield-dropzone ui-corner-all">
				<% if $multiple %>
					<% _t('UploadField.DROPFILES', 'drop files') %>
				<% else %>
					<% _t('UploadField.DROPFILE', 'drop a file') %>
				<% end_if %>
		</div>
		<div class="ss-uploadfield-item-info">
			<label class="ss-uploadfield-item-name"><b>
				<% if $multiple %>
					<% _t('UploadField.ATTACHFILES', 'Attach files') %>
				<% else %>
					<% _t('UploadField.ATTACHFILE', 'Attach a file') %>
				<% end_if %>
			</b></label>
			<label class="ss-uploadfield-fromcomputer ss-ui-button ui-corner-all" title="<% _t('UploadField.FROMCOMPUTERINFO', 'Upload from your computer') %>" data-icon="drive-upload">
				<% _t('UploadField.FROMCOMPUTER', 'From your computer') %>
				<input id="$id" name="$getName" class="$extraClass ss-uploadfield-fromcomputer-fileinput" data-config="$configString" type="file"<% if $multiple %> multiple="multiple"<% end_if %> />
			</label>
			<button class="ss-uploadfield-fromfiles ss-ui-button ui-corner-all" title="<% _t('UploadField.FROMCOMPUTERINFO', 'Select from files') %>" data-icon="network-cloud"><% _t('UploadField.FROMFILES', 'From files') %></button>
			<% if not $autoUpload %>
				<button class="ss-uploadfield-startall ss-ui-button ui-corner-all" title="<% _t('UploadField.STARTALLINFO', 'Start all uploads') %>" data-icon="navigation"><% _t('UploadField.STARTALL', 'Start all') %></button>
			<% end_if %>
			<div class="clear"><!-- --></div>
		</div>
		<div class="clear"><!-- --></div>
	</div>
<% end_if %>
