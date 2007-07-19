<?php
/**
 * Field for displaying NZ GST numbers (usually 8-9 digits in the format ##-###-### or ##-###-####).
 * @see http://www.ird.govt.nz/payroll-employers/software-developers/software-specs/
 */
class GSTNumberField extends TextField {
	
	function jsValidation() {
		$formID = $this->form->FormName();
		
		$jsFunc =<<<JS
Behaviour.register({
	"#$formID": {
		validateGSTNumber: function(fieldName) {
			var el = _CURRENT_FORM.elements[fieldName];
			if(!el || !el.value) return true;
			
			var value = \$F(el);
			if(value.length > 0 && !value.match(/^[0-9]{2}[\-]?[0-9]{3}[\-]?[0-9]{3,4}\$/)) {
				validationError(el,"Please enter a valid GST Number","validation",false);
				return false;
			}
			return true;
		}
	}
});
JS;
		Requirements::customScript($jsFunc, 'func_validateGSTNumber');
		
		return "\$('$formID').validateGSTNumber('$this->name');";
	}
	
	function validate($validator){
		$valid = preg_match(
			'/^[0-9]{2}[\-]?[0-9]{3}[\-]?[0-9]{3,4}$/',
			$this->value
		);
		
		if(!$valid){
			$validator->validationError(
				$this->name, 
				"Please enter a valid GST Number",
				"validation", 
				false
			);
			return false;
		}
		
		return true;
	}
	
}
?>
