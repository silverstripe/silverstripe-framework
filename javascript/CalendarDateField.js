

// _DAYS_IN_MONTH = new Array( 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );

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


DMYCalendarDateField = Class.create();
DMYCalendarDateField.applyTo('div.dmycalendardate');
DMYCalendarDateField.prototype = {
	initialize: function() {
		
		// the hidden field will contain the full date string to make it backwards compatible
		// with the full date fields
		this.hiddenDateField = this.getElementsByTagName('input')[0];
		this.dayField = this.getElementsByTagName('input')[1];
		this.monthField = this.getElementsByTagName('input')[2];
		this.yearField = this.getElementsByTagName('input')[3];
		
		this.hiddenDateField.onchange = this.updateVisibleDate.bind(this);
		this.dayField.onchange = this.updateDate.bind(this);
		this.monthField.onchange = this.updateDate.bind(this);
		this.yearField.onchange = this.updateDate.bind(this);
		
		// this field is updated and then validated before the hidden or visible
		// fields are updated
		
		this.oldDay = '';
		this.oldMonth = '';
		this.oldYear = '';
		
		// these fields are numeric
		// TODO, validate date range
		
		this.dayField.callOnValidate = this.updateDate.bind(this);
		this.monthField.callOnValidate = this.updateDate.bind(this);
		this.yearField.callOnValidate = this.updateDate.bind(this);
		
		this.dayField.setRange( 1, 31 );
		// this.dayField.externalValidate = this.validateDay.bind(this);
		
		this.monthField.setRange( 1, 12 );
		
		this.dayField.nextField = this.monthField;
		this.monthField.nextField = this.yearField;
	
		this.dayField.oldlength = 0;
		this.monthField.oldlength = 0;
		this.yearField.oldlength = 0;
	},
	// C'mon it's a great name for a function
	updateDate: function() {
		this.hiddenDateField.value = this.dayField.value + '/' + this.monthField.value + '/' + this.yearField.value;
		//alert(this.hiddenDateField.name);
	},
	updateVisibleDate: function() {
		var matches = this.hiddenDateField.value.match( /(\d{2})\/(\d{2})\/(\d{4})/ );
		this.dayField.value = matches[1];
		this.monthField.value = matches[2];
		this.yearField.value = matches[3];
	},
	validateDay: function( value ) {
		/*if( this.monthField.value.length == 0 )
			return true;
			
		if( parseInt( value ) > _DAYS_IN_MONTH[parseInt( this.monthField.value )] )
			return false;*/
			
		return true;
	}
};