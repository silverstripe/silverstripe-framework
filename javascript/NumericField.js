NumericField = Class.create();
NumericField.applyTo('input.numeric');
NumericField.prototype = {
	initialize: function() {
		this.oldValue = this.value;	
	},
	
	setRange: function( minValue, maxValue ) {
		this.minValue = minValue;
		this.maxValue = maxValue;	
	},
	
	onkeyup: function() {
		var testValue = this.value;
		
		if( testValue == this.oldValue )
			return;
		
		var length = this.maxLength;
		this.value = '';
		
		var testedOk = true;
		
		var regex = new RegExp( '^\\d{0,' + length + '}$' );
		
		// check that the value is numeric
		if( !testValue.match( regex ) )
			testedOk = false; 
		
		if( testedOk && testValue.length > 0 ) {
			 
			// check that the number is not outside the range
			if( testedOk && typeof this.minValue != 'undefined' && parseInt(testValue) < this.minValue )
				testedOk = false;		
				
			if( testedOk && typeof this.maxValue != 'undefined' && parseInt(testValue) > this.maxValue )
				testedOk = false;
				
			// use any external tests
			if( testedOk && typeof this.externalValidate != 'undefined' && !this.externalValidate( testValue ) )
				testedOk = false;	
						
		}
		
		if( testedOk ) {
			this.oldValue = this.value = testValue;
			
			// DEBUG This produces weird javascript-errors, and is not very useable at all
			// DONT MERGE
			/*
			if( this.value.length == this.maxLength && this.nextField )
				this.nextField.focus();
			*/
				
			if( this.callOnValidate )
				this.callOnValidate();
		} else
			this.value = this.oldValue;
	}	
}