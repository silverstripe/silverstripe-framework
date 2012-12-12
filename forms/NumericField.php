<?php
/**
 * Text input field with validation for numeric values.
 * 
 * @package forms
 * @subpackage fields-formattedinput
 */
class NumericField extends TextField{

	public function Type() {
		return 'numeric text';
	}

	/** PHP Validation **/
	public function validate($validator){
		if($this->value && !is_numeric(trim($this->value))){
			$validator->validationError(
				$this->name,
				_t(
					'NumericField.VALIDATION', "'{value}' is not a number, only numbers can be accepted for this field",
					array('value' => $this->value)
				),
				"validation"
			);
			return false;
		} else{
			return true;
		}
	}
	
	public function dataValue() {
		return (is_numeric($this->value)) ? $this->value : 0;
	}
}
