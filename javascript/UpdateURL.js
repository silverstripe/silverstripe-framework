Behaviour.register({
	'input#Form_EditForm_Title': {
		/**
		 * Get the URL segment to suggest a new field
		 */
		onchange: function() {
			if( this.value.length == 0 )
				return;
			
			var urlSegmentField = $('Form_EditForm_URLSegment');
			
			var newSuggestion = urlSegmentField.suggestNewValue( this.value.toLowerCase() );
			
			var isNew = $('Form_EditForm_ID').value.indexOf("new") == 0;
			
			if( newSuggestion == urlSegmentField.value || isNew || confirm( 'Would you like me to change the URL to:\n\n' + newSuggestion + '/\n\nClick Ok to change the URL, click Cancel to leave it as:\n\n' + urlSegmentField.value ) )
				urlSegmentField.value = newSuggestion;
		}
	}
});