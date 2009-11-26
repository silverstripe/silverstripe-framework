UniqueFormField = Class.create();
UniqueFormField.prototype = {
	validate: function() {
		// check that the value is not in use, and matches the pattern
		var suggested = this.value;
		
		if( this.restrictedValues[suggested] || suggested == null ) {
			suggested = this.suggestNewValue();
			statusMessage(ss.i18n.sprintf(
				ss.i18n._t('UNIQUEFIELD.SUGGESTED', "Changed value to '%s' : %s"),
				suggested,
				this.restrictedMessage
			));
    		this.value = suggested;
		}
	},
	suggestNewValue: function() {
		var parts = this.value.match( /(.*)(\d+)$/ );
		var prefix = '';
		var count = 1;
		
		
		if( parts )
			prefix = parts[1];
		else
			prefix = this.value;
			
		if( prefix.charAt(prefix.length-1) != ' ' )
			prefix = prefix + ' ';

		var suggested = prefix + count;			
			
		while( this.restrictedValues[suggested] )
			suggested = prefix + (++count);
			
		return suggested;
	}
}
