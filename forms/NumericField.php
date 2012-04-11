<?php
/**
 * A Single Numeric field extending a typical 
 * TextField but with validation.
 * @package forms
 * @subpackage fields-formattedinput
 */
class NumericField extends TextField{

	function Type() {
		return 'numeric text';
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
