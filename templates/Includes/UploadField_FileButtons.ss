<% if canEdit %>
	<button class="ss-uploadfield-item-edit ss-ui-button ui-corner-all" title="<% _t('UploadField.EDITINFO', 'Edit this file') %>" data-icon="pencil">
	<% _t('UploadField.EDIT', 'Edit') %>
	<span class="toggle-details">
		<span class="toggle-details-icon"></span>
	</span>

	</button>
	<% if UploadFieldHasRelation %>
	<button data-href="$UploadFieldRemoveLink" class="ss-uploadfield-item-remove ss-ui-button ui-corner-all" title="<% _t('UploadField.REMOVEINFO', 'Remove this file from here, but do not delete it from the file store') %>" data-icon="plug-disconnect-prohibition">
	<% _t('UploadField.REMOVE', 'Remove') %></button>
<% end_if %>
<% end_if %>
<% if canDelete %>
	<button data-href="$UploadFieldDeleteLink" class="ss-uploadfield-item-delete ss-ui-button ui-corner-all" title="<% _t('UploadField.DELETEINFO', 'Permanently delete this file from the file store') %>" data-icon="minus-circle"><% _t('UploadField.DELETE', 'Delete from files') %></button>
<% end_if %>

