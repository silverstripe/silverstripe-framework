<?php
/**
 * A Single Numeric field extending a typical 
 * TextField but with validation.
 * @package forms
 * @subpackage fields-formattedinput
 */
class NumericField extends TextField{
	
	function Field() {
		$html = parent::Field();
		Requirements::javascript(SAPPHIRE_DIR . 'javascript/NumericField.js');
		
		return $html;
	}
	
	function jsValidation() {
		$formID = $this->form->FormName();
		$error = _t('NumericField.VALIDATIONJS', 'is not a number, only numbers can be accepted for this field');
		$jsFunc =<<<JS
Behaviour.register({
	"#$formID": {
		validateNumericField: function(fieldName) {
				el = _CURRENT_FORM.elements[fieldName];
				if(!el || !el.value) return true;
				
			 	if(!isNaN(el.value)) {
			 		return true;
			 	} else {
					validationError(el, "'" + el.value + "' $error","validation");
			 		return false;
			 	}
			}
	}
});
JS;

		Requirements::customScript($jsFunc, 'func_validateNumericField');

		//return "\$('$formID').validateNumericField('$this->name');";
		return <<<JS
if(typeof fromAnOnBlur != 'undefined'){
	if(fromAnOnBlur.name == '$this->name')
		$('$formID').validateNumericField('$this->name');
}else{
	$('$formID').validateNumericField('$this->name');
}
JS;
	}
	
	/** PHP Validation **/
	function validate($validator){
		if($this->value && !is_numeric(trim($this->value))){
 			$validator->validationError(
 				$this->name,
				sprintf(
					_t('NumericField.VALIDATION', "'%s' is not a number, only numbers can be accepted for this field"),
					$this->value
				),
				"validation"
			);
			return false;
		} else{
			return true;
		}
	}
	
	function dataValue() {
		return (is_numeric($this->value)) ? $this->value : 0;
	}
}
?>