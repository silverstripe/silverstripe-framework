<% if $canEdit %>
	<button type="button" class="ss-uploadfield-item-edit ss-ui-button ui-corner-all" title="<%t UploadField.EDITINFO 'Edit this file' %>" data-icon="pencil">
	<%t UploadField.EDIT 'Edit' %>
	<span class="toggle-details">
		<span class="toggle-details-icon"></span>
	</span>
	</button>
<% end_if %>
<button type="button" class="ss-uploadfield-item-remove ss-ui-button ui-corner-all" title="<%t UploadField.REMOVEINFO 'Remove this file from here, but do not delete it from the file store' %>" data-icon="plug-disconnect-prohibition">
<%t UploadField.REMOVE 'Remove' %></button>
<% if $canDelete %>
	<button type="button" data-href="$UploadFieldDeleteLink" class="ss-uploadfield-item-delete ss-ui-button ui-corner-all" title="<%t UploadField.DELETEINFO 'Permanently delete this file from the file store' %>" data-icon="minus-circle"><%t UploadField.DELETE 'Delete from files' %></button>
<% end_if %>
<% if $UploadField.canAttachExisting %>
	<button type="button" class="ss-uploadfield-item-choose-another ss-uploadfield-fromfiles ss-ui-button ui-corner-all" title="<%t UploadField.CHOOSEANOTHERINFO 'Replace this file with another one from the file store' %>" data-icon="network-cloud">
	<%t UploadField.CHOOSEANOTHERFILE 'Choose another file' %></button>
<% end_if %>
