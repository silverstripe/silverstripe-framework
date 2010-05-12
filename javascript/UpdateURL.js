Behaviour.register({
	'input#Form_EditForm_Title': {
		/**
		 * Get the URL segment to suggest a new field
		 */
		onchange: function() {
			if(this.value.length == 0) return;
			if(!$('Form_EditForm_URLSegment')) return;
			
			var urlSegmentField = $('Form_EditForm_URLSegment');
			var isNew = urlSegmentField.value.indexOf("new") == 0;
			var confirmMessage = ss.i18n.sprintf(
				ss.i18n._t('UPDATEURL.CONFIRM_V2', 'Would you like me to change the URL to match this page name?\n\nClick Ok to change the URL, click Cancel to leave it as:\n\n%s'),
				urlSegmentField.value
			);
			
			if(isNew  || confirm(confirmMessage)) {
				urlSegmentField.value = '';
			}
			// If you type in Page name, the Navigation Label and Meta Title should automatically update the first time
			// @todo: Change file name from UpdateURL to something more geneneric since we now do more than update the URL.
			if($('Form_EditForm_MetaTitle') && $('Form_EditForm_MenuTitle').value.indexOf("New") == 0 ) {
				$('Form_EditForm_MenuTitle').value = this.value;
			}
			// @todo see if updating this is confusing (Q: why isn't my page title changing? A: Check the Meta-Data tab)
			if($('Form_EditForm_MetaTitle') && $('Form_EditForm_MetaTitle').value.length == 0 ) {
				$('Form_EditForm_MetaTitle').value = this.value;
			}
		}
	}
});
