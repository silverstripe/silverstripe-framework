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

UniqueRestrictedTextField = Class.extend('UniqueFormField');
UniqueRestrictedTextField.applyTo('input.UniqueRestrictedTextField');
UniqueRestrictedTextField.prototype = {
	initialize: function() {
		this.loadMessages();
		if(this.loadRestrictedValues){ this.loadRestrictedValues()};
		this.loadRestrictedChars();
		this.onblur = this.validate.bind(this);
	},
	loadRestrictedChars: function() {
		this.charRegex = new RegExp( $(this.id + '-restricted-chars').value, 'g' );
		this.charReplacement = $(this.id + '-restricted-chars-replace').value;
		this.trimRegex = new RegExp( '^[' + this.charReplacement + ']+|[' + this.charReplacement + ']+$', 'g' );
	},
	suggestNewValue: function( fromString ) {
		var prefix = '';
		var count = 1;
		var suggested = fromString || this.value;
		
		if( suggested.length == 0 ) {
			suggested = $('Form_EditForm_Title').value.toLowerCase();
		} 
		
		var escaped = suggested.replace(this.charRegex, this.charReplacement);
		escaped = escaped.replace( this.trimRegex, '' );
		
		// this.loadRestrictedValues is never called, i think someone missed a function.. 
		if(this.restrictedValues){
			if( !this.restrictedValues[escaped] )
				return escaped;
		}
		
		var prefix = escaped;
		
		if( prefix.charAt(prefix.length-1) != this.charReplacement )
			prefix = prefix + this.charReplacement;

		suggested = prefix;			
		suggested = suggested.replace( this.charRegex, this.charReplacement );
		suggested = suggested.replace( this.trimRegex, '' );
		
		if(this.restrictedValues){
			while( this.restrictedValues[suggested] ) {
				suggested = prefix + (++count);
				suggested = suggested.replace( this.charRegex, this.charReplacement );
				suggested = suggested.replace( this.trimRegex, '' );
			}
		}
		
			
		return suggested;
	},
	validate: function() {
		// check that the value is not in use, and matches the pattern
		var suggested = this.value;
		if(this.restrictedValues){
			var suggestedValue = this.restrictedValues[suggested];
		}
		
		if( suggested == null || suggested.length == 0 || suggestedValue || suggested.match( this.charRegex ) ) {
		    var message;
			if( suggested == null )
				message = ss.i18n._t('UNIQUEFIELD.ENTERNEWVALUE', 'You will need to enter a new value for this field');
			else if( suggested.length == 0 )
				message = ss.i18n._t('UNIQUEFIELD.CANNOTLEAVEEMPTY', 'This field cannot be left empty');
			else if( suggestedValue )
				message = this.restrictedMessage;
			else
				message = this.charMessage;

			suggested = this.suggestNewValue();
			statusMessage(ss.i18n.sprintf(
				ss.i18n._t('UNIQUEFIELD.SUGGESTED', "Changed value to '%s' : %s"),
				suggested,
				message
			));
		}
		
		this.value = suggested;
	},
	loadMessages: function() {
		this.restrictedMessage = $(this.id + '-restricted-message').value;
		this.charMessage = $(this.id + '-restricted-chars-message').value;
	}
}

RestrictedTextField = Class.create();
RestrictedTextField.applyTo('input.text.restricted');
RestrictedTextField.prototype = {
	
	initialize: function() {
		this.restrictedChars = $(this.id + '-restricted-chars').value;
		// this.restrictedRegex = new RegExp( $(this.id.'-restricted-chars').value, 'g' );
	},
	
	onkeyup: function() {
		
		var lastChar = this.value.charAt(this.value.length - 1);
		
		for( var index = 0; index < this.restrictedChars.length; index++ ) {
			if( lastChar == this.restrictedChars.charAt(index) ) {
				alert(ss.i18n.sprintf(
					ss.i18n._t('RESTRICTEDTEXTFIELD.CHARCANTBEUSED', "The character '%s' cannot be used in this field"),
					lastChar
				));
				this.value = this.value.substring( 0, this.value.length - 1 );
			}
		}
	}
}
