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

		 	if(el.value.match(/^[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/i)) {
		 		return true;
		 	} else {
				validationError(el, "$error","validation");
		 		return false;
		 	} 	
		}
	}
});
JS;
		//fix for the problem with more than one form on a page.
		Requirements::customScript($jsFunc, 'func_validateEmailField' .'_' . $formID);

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
	
	/**
	 * Validates for RFC 2822 compliant email adresses.
	 * 
	 * @see http://www.regular-expressions.info/email.html
	 * @see http://www.ietf.org/rfc/rfc2822.txt
	 * 
	 * @param Validator $validator
	 * @return String
	 */
	function validate($validator){
		$this->value = trim($this->value);
		
		$pcrePattern = '^[a-z0-9!#$%&\'*+/=?^_`{|}~-]+(?:\\.[a-z0-9!#$%&\'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$';


		// PHP uses forward slash (/) to delimit start/end of pattern, so it must be escaped
		$pregSafePattern = str_replace('/', '\\/', $pcrePattern);

		if($this->value && !preg_match('/' . $pregSafePattern . '/i', $this->value)){
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