<label class="ss-uploadfield-item-edit ss-ui-button ui-corner-all" title="<% _t('UploadField.EDITINFO', 'Edit this file') %>"><% _t('UploadField.EDIT', 'Edit') %></label>
<% if UploadFieldHasRelation %>
	<label data-href="$UploadFieldRemoveLink" class="ss-uploadfield-item-remove ss-ui-button ui-corner-all" title="<% _t('UploadField.REMOVEINFO', 'Remove this file from here, but do not delete it from the file store') %>"><% _t('UploadField.REMOVE', 'Remove') %></label>
<% end_if %>
<label data-href="$UploadFieldDeleteLink" class="ss-uploadfield-item-delete ss-ui-button ui-corner-all" title="<% _t('UploadField.DELETEINFO', 'Permanently delete this file from the file store') %>"><% _t('UploadField.DELETE', 'Delete from files') %></label>