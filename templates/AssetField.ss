<ul class="ss-uploadfield-files files">
	<% if $Value %>
		<li class="ss-uploadfield-item template-download" data-filename="$Value.Filename">
			<div class="ss-uploadfield-item-preview preview"><span>
				<img alt="$Name.ATT" src="$Value.ThumbnailURL($PreviewMaxWidth,$PreviewMaxHeight).ATT" />
			</span></div>
			<div class="ss-uploadfield-item-info">
				<input type='hidden' value='$Value.Filename.ATT' name='{$Name}[Filename]' />
				<input type='hidden' value='$Value.Hash.ATT' name='{$Name}[Hash]' />
				<input type='hidden' value='$Value.Variant.ATT' name='{$Name}[Variant]' />
				<label class="ss-uploadfield-item-name">
					<span class="name">$Value.Basename.XML</span>
					<span class="size">$Value.Size.XML</span>
					<div class="clear"><!-- --></div>
				</label>
				<div class="ss-uploadfield-item-actions">
					<% if $isActive %>
						$UploadFieldFileButtons.RAW
					<% end_if %>
				</div>
			</div>
		</li>
	<% end_if %>
</ul>
<% if $canUpload %>
	<div class="ss-uploadfield-item ss-uploadfield-addfile<% if $CustomisedItems %> borderTop<% end_if %>">
		<div class="ss-uploadfield-item-preview ss-uploadfield-dropzone ui-corner-all">
			<%t UploadField.DROPFILE 'drop a file' %>
		</div>
		<div class="ss-uploadfield-item-info">
			<label class="ss-uploadfield-item-name">
				<b><%t UploadField.ATTACHFILE 'Attach a file' %></b>
				<% if $canPreviewFolder %>
					<small>(<%t UploadField.UPLOADSINTO 'saves into /{path}' path=$FolderName %>)</small>
				<% end_if %>
			</label>
			<label class="ss-uploadfield-fromcomputer ss-ui-button ui-corner-all" title="<%t UploadField.FROMCOMPUTERINFO 'Upload from your computer' %>" data-icon="drive-upload">
				<%t UploadField.FROMCOMPUTER 'From your computer' %>
				<input id="$ID" name="{$Name}[Upload]" class="$extraClass ss-uploadfield-fromcomputer-fileinput" data-config="{$ConfigString.ATT}" type="file" />
			</label>
			<% if not $autoUpload %>
				<button class="ss-uploadfield-startall ss-ui-button ui-corner-all" data-icon="navigation"><%t UploadField.STARTALL 'Start all' %></button>
			<% end_if %>
			<div class="clear"><!-- --></div>
		</div>
		<div class="clear"><!-- --></div>
	</div>
<% end_if %>
