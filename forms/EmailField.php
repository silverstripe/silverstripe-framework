<?php
/**
 * Text field with Email Validation.
 * @package forms
 * @subpackage fields-formattedinput
 */
class EmailField extends TextField {
	
	function jsValidation() {
		$formID = $this->form->FormName();
		$error = _t('EmailField.VALIDATIONJS', 'Please enter an email address.');
		$jsFunc =<<<JS
Behaviour.register({
	"#$formID": {
		validateEmailField: function(fieldName) {
			var el = _CURRENT_FORM.elements[fieldName];
			if(!el || !el.value) return true;

		 	if(el.value.match(/^([a-zA-Z0-9_+\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/)) {
		 		return true;
		 	} else {
				validationError(el, "$error","validation");
		 		return false;
		 	} 	
		}
	}
});
JS;

		Requirements::customScript($jsFunc, 'func_validateEmailField');

		//return "\$('$formID').validateEmailField('$this->name');";
		return <<<JS
if(typeof fromAnOnBlur != 'undefined'){
	if(fromAnOnBlur.name == '$this->name')
		$('$formID').validateEmailField('$this->name');
}else{
	$('$formID').validateEmailField('$this->name');
}
JS;
	}
	
	function validate($validator){
		$this->value = trim($this->value);
		if($this->value && !ereg('^([a-zA-Z0-9_+\.\-]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$', $this->value)){
 			$validator->validationError(
 				$this->name,
				_t('EmailField.VALIDATION', "Please enter an email address."),
				"validation"
			);
			return false;
		} else{
			return true;
		}
	}
}
?>