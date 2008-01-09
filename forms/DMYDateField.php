<?php

/**
 * @package forms
 * @subpackage fields-datetime
 */

/**
 * Displays a date field with day, month and year boxes, with a calendar to select
 * the date
 * @package forms
 * @subpackage fields-datetime
 */
class DMYDateField extends CalendarDateField {
	
	function setValue( $value ) {
		if( is_array( $value ) && $value['Day'] && $value['Month'] && $value['Year'] )
			$this->value = $value['Year'] . '-' . $value['Month'] . '-' . $value['Day'];
		else if(is_array($value)&&(!$value['Day']||!$value['Month']||!$value['Year']))  
 			$this->value = null; 
 		else if(is_string($value)) 
			$this->value = $value;
	}
	
	function Field() {
		Requirements::javascript("jsparty/calendar/calendar.js");
		Requirements::javascript("jsparty/calendar/lang/calendar-en.js");
		Requirements::javascript("jsparty/calendar/calendar-setup.js");
		Requirements::css("sapphire/css/CalendarDateField.css");
		Requirements::css("jsparty/calendar/calendar-win2k-1.css");
		Requirements::javascript("sapphire/javascript/CalendarDateField.js");

		$field = DateField::Field();

		$id = $this->id();
		$val = $this->attrValue();
		
		if( preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $val ) ) {
			$dateArray = explode( '/', $val );
			
			$val = $dateArray[2] . '-' . $dateArray[1] . '-' . $dateArray[0];
		}
		
		$dateArray = explode( '-', $val );
		
		$day = $dateArray[2];
		$month = $dateArray[1];
		$year = $dateArray[0];
		
		$fieldName = $this->name;
		
		return <<<HTML
			<div class="dmycalendardate">
				<input type="hidden" id="$id" name="{$this->name}" value="$val" />
				<input type="text" id="$id-day" class="day numeric" name="{$fieldName}[Day]" value="$day" maxlength="2" />/
				<input type="text" id="$id-month" class="month numeric" name="{$fieldName}[Month]" value="$month" maxlength="2" />/
				<input type="text" id="$id-year" class="year numeric" name="{$fieldName}[Year]" value="$year" maxlength="4" />
				<div class="calendarpopup" id="{$id}-calendar"></div>
			</div>
HTML;
	}
	
	function validate($validator) 
 	{ 
 		if(!empty ($this->value) && !preg_match('/^[0-90-9]{2,4}\-[0-9]{1,2}\-[0-90-9]{1,2}$/', $this->value)) 
 		{ 
 			$validator->validationError( 
 				$this->name,  
 				_t('DMYDateField.VALIDDATEFORMAT', "Please enter a valid  date format (DD-MM-YYYY)."),  
 				"validation",  
 				false 
 			); 
 			return false; 
 		} 
 	return true; 
 	} 
}
?>