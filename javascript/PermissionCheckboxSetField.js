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
						// only update attributes if previous values have been saved
						var oldChecked = $(this).data('SecurityAdmin.oldChecked');
						var oldDisabled = $(this).data('SecurityAdmin.oldDisabled');
						if(oldChecked != null) $(this).attr('checked', oldChecked);
						if(oldDisabled != null) $(this).attr('disabled', oldDisabled);
					});
				}
			}
		},
		/**
		 * Automatically check all "CMS section" checkboxes when "Access to all CMS interfaces" is ticked.
		 * 
		 * @todo This should really be abstracted into a declarative dependency system
		 * instead of custom logic.
		 */
		'.permissioncheckboxset .valCMS_ACCESS_LeftAndMain input': {
			getCheckboxesExceptThisOne: function() {
				return $(this).parents('.field:eq(0)').find('li').filter(function(i) {
					return ($(this).attr('class').match(/CMS_ACCESS_/));
				}).find('.checkbox').not(this);
			},
			initialize: function() {
				var checkboxes = this.getCheckboxesExceptThisOne();
				if($(this).is(':checked')) {
					checkboxes.each(function() {
						$(this).attr('disabled', 'disabled');
						$(this).attr('checked', 'checked');
					});
				}
			},
			onclick: function(e) {
				var checkboxes = this.getCheckboxesExceptThisOne();
				if($(this).is(':checked')) {
					checkboxes.each(function() {
						$(this).attr('disabled', 'disabled');
						$(this).attr('checked', 'checked');
					});
				} else {
					checkboxes.each(function() {
						$(this).attr('checked', '');
						$(this).attr('disabled', '');
					});
				}
			}
		}
	});
}(jQuery));