(function($) {
	
	$('#Form_DeleteFileForm_action_delete').click(function(e) {
		var deleteMessage = ss.i18n._t('FILEIFRAMEFIELD.CONFIRMDELETE', 'Are you sure you want to delete this file?');
		
		if(typeof(parent.jQuery.fn.dialog) != 'undefined') {
			var buttons     = {};
			var $dialog     = undefined;
			var $deleteForm = $('#Form_DeleteFileForm');
			var $deleteFile = $('#Form_DeleteFileForm_DeleteFile');
			
			buttons[ss.i18n._t('FILEIFRAMEFIELD.DELETEFILE', 'Delete File')] = function() {
				$deleteFile.attr('value', 'true');
				$deleteForm.submit();
				
				$dialog.dialog('close');
			};
			
			buttons[ss.i18n._t('FILEIFRAMEFIELD.UNATTACHFILE', 'Un-Attach File')] = function() {
				$deleteForm.submit();
				$dialog.dialog('close');
			};
			
			buttons[ss.i18n._t('CANCEL', 'Cancel')] = function() {
				$dialog.dialog('close');
			};
			
			$dialog = parent.jQuery('<p><span class="ui-icon ui-icon-alert" style="float:left;margin-right:5px;"></span>' + deleteMessage + '</p>').dialog({
				bgiframe: true,
				resizable: false,
				modal: true,
				height: 140,
				overlay: {
					backgroundColor: '#000',
					opacity: 0.5
				},
				title: ss.i18n._t('FILEIFRAMEFIELD.DELETEIMAGE', 'Delete Image'),
				buttons: buttons
			});
			
			e.preventDefault();
		} else if(!confirm(deleteMessage)) {
			e.preventDefault();
		}
	});
	
})(jQuery);