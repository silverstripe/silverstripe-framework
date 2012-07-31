/**
 * File: SecurityAdmin.js
 */
(function($) {
	
	var refreshAfterImport = function(e) {
		// Check for a message <div>, an indication that the form has been submitted.
		var existingFormMessage = $($(this).contents()).find('.message');
		if(existingFormMessage && existingFormMessage.html()) {
			// Refresh member listing
			var memberTableField = $(window.parent.document).find('#Form_EditForm_Members').get(0);
			if(memberTableField) memberTableField.refresh();
			
			// Refresh tree
			var tree = $(window.parent.document).find('.cms-tree').get(0);
			if(tree) tree.reload();
		}
	};
	
	/**
	 * Refresh the member listing every time the import iframe is loaded,
	 * which is most likely a form submission.
	 */
	$('#MemberImportFormIframe, #GroupImportFormIframe').entwine({
		onadd: function() {
			this._super();
			// TODO entwine can't seem to bind to iframe load events
			$(this).bind('load', refreshAfterImport);
		}
	});

	$.entwine('ss', function($){
		/**
		 * Class: #Permissions .checkbox[value=ADMIN]
		 * 
		 * Automatically check and disable all checkboxes if ADMIN permissions are selected.
		 * As they're disabled, any changes won't be submitted (which is intended behaviour),
		 * checking all boxes is purely presentational.
		 */
		$('#Permissions .checkbox[value=ADMIN]').entwine({
			onmatch: function() {
				this.toggleCheckboxes();

				this._super();
			},
			onunmatch: function() {
				this._super();
			},
			/**
			 * Function: onclick
			 */
			onclick: function(e) {
				this.toggleCheckboxes();
			},
			/**
			 * Function: toggleCheckboxes
			 */
			toggleCheckboxes: function() {
				var self = this, checkboxes = this.parents('.field:eq(0)').find('.checkbox').not(this);
				
				if(this.is(':checked')) {
					checkboxes.each(function() {
						$(this).data('SecurityAdmin.oldChecked', $(this).attr('checked'));
						$(this).data('SecurityAdmin.oldDisabled', $(this).attr('disabled'));
						$(this).attr('disabled', 'disabled');
						$(this).attr('checked', 'checked');
					});
				} else {
					checkboxes.each(function() {
						$(this).attr('checked', $(this).data('SecurityAdmin.oldChecked'));
						$(this).attr('disabled', $(this).data('SecurityAdmin.oldDisabled'));
					});
				}
			}
		});
	});
	
}(jQuery));
