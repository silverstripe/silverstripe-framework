<?php
/**
 * Displays a date field with day, month and year boxes, with a calendar to select
 * the date.
 * 
 * @todo Add localization support, see http://open.silverstripe.com/ticket/2931
 *
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
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/CalendarDateField.js");

		$field = DateField::Field();

		$id = $this->id();
		$val = $this->attrValue();
		
		if( preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $val ) ) {
			$dateArray = explode( '/', $val );
			
			$val = $dateArray[2] . '-' . $dateArray[1] . '-' . $dateArray[0];
		}

		$day = $month = $year = null;
		if($val) {
			$dateArray = explode( '-', $val );
		
			$day = $dateArray[2];
			$month = $dateArray[1];
			$year = $dateArray[0];
		}
		
		$fieldName = $this->name;
		
		return <<<HTML
			<div class="dmycalendardate">
				<input type="text" id="$id-day" class="day" name="{$fieldName}[Day]" value="$day" maxlength="2" />/
				<input type="text" id="$id-month" class="month" name="{$fieldName}[Month]" value="$month" maxlength="2" />/
				<input type="text" id="$id-year" class="year" name="{$fieldName}[Year]" value="$year" maxlength="4" />
				<div class="calendarpopup" id="{$id}-calendar"></div>
			</div>
HTML;
	}
	
	function validate($validator) 
 	{ 
 		if(!empty ($this->value) && !preg_match('/^([0-9][0-9]){1,2}\-[0-9]{1,2}\-[0-9]{1,2}$/', $this->value)) 
 		{ 
 			$validator->validationError( 
 				$this->name,  
 				_t('DMYDateField.VALIDDATEFORMAT', "Please enter a valid date format (DD-MM-YYYY)."),  
 				"validation",  
 				false 
 			); 
 			return false; 
 		} 
 	return true; 
 	} 

	function jsValidation() {
		if(Validator::get_javascript_validator_handler() == 'none') {
			return '';
		}
		$formID = $this->form->FormName(); 
		$error = _t('DateField.VALIDATIONJS', 'Please enter a valid date format (DD/MM/YYYY).');
		$error = 'Please enter a valid date format (DD/MM/YYYY) from dmy.';
		$jsFunc =<<<JS
Behaviour.register({
	"#$formID": {
		validateDMYDate: function(fieldName) {
			var day_value = \$F(_CURRENT_FORM.elements[fieldName+'[Day]']);
			var month_value = \$F(_CURRENT_FORM.elements[fieldName+'[Month]']);
			var year_value = \$F(_CURRENT_FORM.elements[fieldName+'[Year]']);
			var value = day_value + '/' + month_value + '/' + year_value;

			if(value && value.length > 0 && !value.match(/^[0-9]{1,2}\/[0-9]{1,2}\/([0-9][0-9]){1,2}\$/)) {
				validationError(_CURRENT_FORM.elements[fieldName+'[Day]'],"$error","validation",false);
				return false;
			}
			return true;
		}
	}
});
JS;
		Requirements :: customScript($jsFunc, 'func_validateDMYDate_'.$formID);
		
//		return "\$('$formID').validateDate('$this->name');";
		return <<<JS
if(\$('$formID')){
	if(typeof fromAnOnBlur != 'undefined'){
		if(fromAnOnBlur.name == '$this->name')
			\$('$formID').validateDMYDate('$this->name');
	}else{
		\$('$formID').validateDMYDate('$this->name');
	}
}
JS;
	}
}
?>