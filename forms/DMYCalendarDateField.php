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
class DMYCalendarDateField extends CalendarDateField {
	
	function Field() {
		Requirements::javascript(THIRDPARTY_DIR . "/calendar/calendar.js");
		Requirements::javascript(THIRDPARTY_DIR . "/calendar/lang/calendar-en.js");
		Requirements::javascript(THIRDPARTY_DIR . "/calendar/calendar-setup.js");
		Requirements::css(SAPPHIRE_DIR . "/css/CalendarDateField.css");
		Requirements::css(THIRDPARTY_DIR . "/calendar/calendar-win2k-1.css");
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/NumericField.js");
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/CalendarDateField.js");

		$field = DateField::Field();

		$id = $this->id();
		$val = $this->attrValue();
		
		$day = $month = $year = null;
		
		if( preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $val ) ) {
			$dateArray = explode( '/', $val );
			$val = $dateArray[2] . '-' . $dateArray[1] . '-' . $dateArray[0];
		}
		
		if($val) {
			$dateArray = explode( '-', $val );
			$day = $dateArray[2];
			$month = $dateArray[1];
			$year = $dateArray[0];
		} 
		
		if(preg_match('/(.*)[(.+)]$/', $this->name, $fieldNameParts)) {
			$fieldNamePrefix = $fieldNameParts[1];
			$fieldName = $fieldNameParts[2];
		} else {
			$fieldNamePrefix = $this->name;
			$fieldName = $this->name;
		}
		
		return <<<HTML
			<div class="dmycalendardate">
				<input type="hidden" id="$id" name="{$this->name}" value="$val" />
				<input type="text" id="$id-day" class="day numeric" name="{$fieldNamePrefix}[Day]" value="$day" maxlength="2" />/
				<input type="text" id="$id-month" class="month numeric" name="{$fieldNamePrefix}[Month]" value="$month" maxlength="2" />/
				<input type="text" id="$id-year" class="year numeric" name="{$fieldNamePrefix}[Year]" value="$year" maxlength="4" />
				<div class="calendarpopup" id="{$id}-calendar"></div>
			</div>
HTML;
	}
	
	function jsValidation() {
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
			if(day_value || month_value || year_value){
				var value = day_value + '/' + month_value + '/' + year_value;

				if(value && value.length > 0 && !value.match(/^[0-9]{1,2}\/[0-9]{1,2}\/([0-9][0-9]){1,2}\$/)) {
					validationError(_CURRENT_FORM.elements[fieldName+'[Day]'],"$error","validation",false);
					return false;
				}
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