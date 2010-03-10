(function($) {
	Behaviour.register({
		/**
		 * Automatically check and disable all checkboxes if ADMIN permissions are selected.
		 * As they're disabled, any changes won't be submitted (which is intended behaviour),
		 * checking all boxes is purely presentational.
		 */
		'.permissioncheckboxset .valADMIN input': {
			initialize: function() {
				this.toggleCheckboxes();
			},
			onclick: function(e) {
				this.toggleCheckboxes();
			},
			toggleCheckboxes: function() {
				var checkboxes = $(this).parents('.field:eq(0)').find('.checkbox').not(this);

				if($(this).is(':checked')) {
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
		}
	});
}(jQuery));