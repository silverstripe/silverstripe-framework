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
						$UploadFieldFileButtons
					</div>
				</div>
				<div class="ss-uploadfield-item-editform loading">
					<iframe frameborder="0" src="$UploadFieldEditLink"></iframe>
				</div>
			</li>
		<% end_loop %>
	<% end_if %>
</ul>
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
		<label class="ss-uploadfield-fromcomputer ss-ui-button ui-corner-all" title="<% _t('UploadField.FROMCOMPUTERINFO', 'Upload from your computer') %>" for="$id">
			<input id="$id" name="$getName" class="$extraClass" data-config="$configString" type="file"<% if $multiple %> multiple="multiple"<% end_if %><% if $TabIndex %> tabindex="$TabIndex"<% end_if %> />
			<span><% _t('UploadField.FROMCOMPUTER', 'From your computer') %></span>
		</label>
		<label class="ss-uploadfield-fromfiles ss-ui-button ui-corner-all" title="<% _t('UploadField.FROMCOMPUTERINFO', 'Select from from files') %>"><% _t('UploadField.FROMCOMPUTER', 'From files') %></label>
		<% if not $config.autoUpload %>
			<label class="ss-uploadfield-startall ss-ui-button ui-corner-all" title="<% _t('UploadField.STARTALLINFO', 'Start all uploads') %>"><% _t('UploadField.STARTALL', 'Start all') %></label>
		<% end_if %>
		<div class="clear"><!-- --></div>
	</div>
	<div class="clear"><!-- --></div>
</div>