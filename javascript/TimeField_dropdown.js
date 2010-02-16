var DropdownTime = [];

TimeBehaviour = {
	initialise : function () {
		this.isOpen = false;
		
		this.icon = $( this.id + '-icon' );
		
		this.icon.onclick = this.toggle.bind( this );
		
		this.dropdowntime = $( this.id + '-dropdowntime' );
		
		var dropdown = 
			'<select id="' + this.id + '-dropdowntime' + '-select' + '" size="18">' +
				'<option value="12:00 am" class="midnight">Midnight</option>' +
				'<option value="01:00 am" class="evening">01:00 am</option>' +
				'<option value="02:00 am" class="evening">02:00 am</option>' +
				'<option value="03:00 am" class="evening">03:00 am</option>' +
				'<option value="04:00 am" class="evening">04:00 am</option>' +
				'<option value="05:00 am" class="evening">05:00 am</option>' +
				'<option value="06:00 am" class="morning">06:00 am</option>' +
				'<option value="07:00 am" class="morning">07:00 am</option>' +
				'<option value="07:30 am" class="morning">07:30 am</option>' +
				'<option value="08:00 am" class="morning" selected>08:00 am</option>' +
				'<option value="08:30 am" class="morning">08:30 am</option>' +
				'<option value="09:00 am" class="morning">09:00 am</option>' +
				'<option value="09:30 am" class="morning">09:30 am</option>' +
				'<option value="10:00 am" class="morning">10:00 am</option>' +
				'<option value="10:30 am" class="morning">10:30 am</option>' +
				'<option value="11:00 am" class="morning">11:00 am</option>' +
				'<option value="11:30 am" class="morning">11:30 am</option>' +
				'<option value="12:00 pm" class="noon">Noon</option>' +
				'<option value="12:30 pm" class="afternoon">12:30 pm</option>' +
				'<option value="01:00 pm" class="afternoon">01:00 pm</option>' +
				'<option value="01:30 pm" class="afternoon">01:30 pm</option>' +
				'<option value="02:00 pm" class="afternoon">02:00 pm</option>' +
				'<option value="02:30 pm" class="afternoon">02:30 pm</option>' +
				'<option value="03:00 pm" class="afternoon">03:00 pm</option>' +
				'<option value="03:30 pm" class="afternoon">03:30 pm</option>' +
				'<option value="04:00 pm" class="afternoon">04:00 pm</option>' +
				'<option value="04:30 pm" class="afternoon">04:30 pm</option>' +
				'<option value="05:00 pm" class="afternoon">05:00 pm</option>' +
				'<option value="05:30 pm" class="afternoon">05:30 pm</option>' +
				'<option value="06:00 pm" class="evening">06:00 pm</option>' +
				'<option value="06:30 pm" class="evening">06:30 pm</option>' +
				'<option value="07:00 pm" class="evening">07:00 pm</option>' +
				'<option value="07:30 pm" class="evening">07:30 pm</option>' +
				'<option value="08:00 pm" class="evening">08:00 pm</option>' +
				'<option value="08:30 pm" class="evening">08:30 pm</option>' +
				'<option value="09:00 pm" class="evening">09:00 pm</option>' +
				'<option value="09:30 pm" class="evening">09:30 pm</option>' +
				'<option value="10:00 pm" class="evening">10:00 pm</option>' +
				'<option value="10:30 pm" class="evening">10:30 pm</option>' +
				'<option value="11:00 pm" class="evening">11:00 pm</option>' +
				'<option value="11:30 pm" class="evening">11:30 pm</option>' +
			'</select>';
		
		this.dropdowntime.innerHTML = dropdown;
				 	
		DropdownTime[ DropdownTime.length ] = this.dropdowntime;
		
		this.selectTag = $( this.id + '-dropdowntime' + '-select' );
		
		this.selectTag.onchange = this.updateValue.bind( this );
	},
	
	toggle : function() {
		if( this.isOpen )
			this.close();
		else
			this.open();
	},
	
	open : function() {
		this.isOpen = true;
		for( var i = 0; i < DropdownTime.length ; i++ ) {
			var dropdowntime = DropdownTime[i];
			if( dropdowntime == this.dropdowntime )
				Element.addClassName( dropdowntime, 'focused' );
			else
				Element.removeClassName( dropdowntime, 'focused' );
		}
	},
	
	close : function() {
		this.isOpen = false;
		Element.removeClassName( this.dropdowntime, 'focused' );
	},
	
	updateValue : function() {
		if( this.selectTag.selectedIndex != null ) {
			var timeValue = this.selectTag.options[ this.selectTag.selectedIndex ].value;
			this.value = timeValue;
			if(this.onchange) this.onchange();
		}
		this.close();
	}
};

Behaviour.register({
	'div.dropdowntime input' : TimeBehaviour,
	'li.dropdowntime input' : TimeBehaviour
});