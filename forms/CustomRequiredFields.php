<?php
/**
 * CustomRequiredFields allow you to create your own validation on forms, while still having the ability to have required fields (as used in [RequiredFields](http://api.silverstripe.org/current/sapphire/form/RequiredFields.html)).
 * 
 * The constructor of CustomRequiredFields takes an array. Each array element is one of two things - either the name of a field that is required, or an array containing two items, 'js' and 'php'. These items are functions called to validate in javascript or php respectively.
 * 
 * Some useful javascript: 
 * 1.  _CURRENT_FORM is the current form
 * 2.  _CURRENT_FORM.elements is an array of the fields
 * 3.  validationError(element, message, type) will create a validation error
 * 4.  clearErrorMessage(element) will clear the validation error
 * 5.  require('FieldName') create a required field ($this->requireField('FieldName') is the php equivalent)
 * 
 * An example for creating required fields only if payment type is CreditCard:
 * 
 * <code>
 * new CustomRequiredFields(
 * 	array(
 * 	        "PaymentMethod",
 * 	        array(
 * 	                "js" => "
 * 	                        for( var i = 0; i <= this.elements.PaymentMethod.length -1; i++){
 * 	                                if(this.elements.PaymentMethod[i].value == 'CC' && this.elements.PaymentMethod[i].checked == true){
 * 	                                        require('CardHolderName');
 * 	                                        require('CreditCardNumber');
 * 	                                        require('DateExpiry');
 * 	                                }
 * 	                        }
 * 	                        
 * 	                ",
 * 	                "php" => 'if($data[PaymentMethod] == "CC") {
 * 	                        $this->requireField($field,"$field is required","required");
 * 	                        $this->requireField("CardHolderName", $data);
 * 	                        $this->requireField("CreditCardNumber", $data);
 * 	                        $this->requireField("DateExpiry", $data);
 * 	                }',
 * 	        )
 * 	)
 * );
 * </code>
 * 
 * And example for confirming mobile number and email address:
 * 
 * <code>
 * $js = <<<JS
 * if(_CURRENT_FORM.elements["MobileNumberConfirm"].value == _CURRENT_FORM.elements["MobileNumber"].value) {
 *    clearErrorMessage(_CURRENT_FORM.elements["MobileNumberConfirm"].parentNode);
 * } else {
 *    validationError(_CURRENT_FORM.elements["MobileNumberConfirm"], "Mobile numbers do not match", "validation");
 * }
 * JS;
 * 
 * $js2 = <<<JS2
 * if(_CURRENT_FORM.elements["EmailConfirm"].value == _CURRENT_FORM.elements["Email"].value) {
 *    clearErrorMessage(_CURRENT_FORM.elements["EmailConfirm"].parentNode);
 * } else {
 *    validationError(_CURRENT_FORM.elements["EmailConfirm"], "Email addresses do not match", "validation");
 * }
 * JS2;
 * 
 * //create validator
 * $validator=new CustomRequiredFields(array('FirstName', 'Surname', 'Email', 'MobileNumber', array('js' => $js, 'php' => 'return true;'), array('js' => $js2, 'php'=>'return true;')));
 * </code>
 * 
 * @package forms
 * @subpackage validators
 */
class CustomRequiredFields extends RequiredFields{
	protected $required;
		
	/**
	 * Pass each field to be validated as a seperate argument
	 * @param $required array The list of required fields
	 */
	function __construct($required) {
		$this->required = $required;
	}
	
	/**
	 * Creates the client side validation from form fields
	 * which is generated at the header of each page 
	 */
	function javascript() {
		$code = '';
		$fields = $this->form->Fields();
		foreach($fields as $field){
			//if the field type has some special specific specification for validation of itself
			$valid = $field->jsValidation();
			if($valid){
				$code .= $valid;
			}
		}
		if(is_array($this->required)){

			foreach($this->required as $field) {
				if(is_array($field) && isset($field['js'])){
					$code .= $field['js'] . "\n";
				}else if($fields->dataFieldByName($field)) {
					$code .= "						require('$field');\n";
					//Tabs for output tabbing :-)
				}
			}
		}else{
			USER_ERROR("CustomRequiredFields::requiredfields is not set / not an array",E_USER_WARNING);
		}
		return $code;
	}
	
	/**
	 * Creates the server side validation from form fields
	 * which is executed on form submission
	 */
	function php($data) {
		$fields = $this->form->Fields();
		$valid = true;
		foreach($fields as $field) {
			$valid = ($field->validate($this) && $valid);
		}
		if($this->required){
                        foreach($this->required as $key => $fieldName) {
                if(is_string($fieldName)) $formField = $fields->dataFieldByName($fieldName);
				if(is_array($fieldName) && isset($fieldName['php'])){
					eval($fieldName['php']);
				}else if($formField) {
					// if an error is found, the form is returned.
					if(!strlen($data[$fieldName]) || preg_match('/^\s*$/', $data[$fieldName])) {
						$this->validationError(
							$fieldName,
                                                        sprintf(_t('Form.FIELDISREQUIRED', "%s is required."),
                                                                $formField->Title()),
							"required"
						);
						return false;
					}
				}
			}	
		}
		return $valid;
	}
	
	/**
	 * allows you too add more required fields to this object after construction.
	 */
	function appendRequiredFields($requiredFields){
		$this->required = array_merge($this->required,$requiredFields->getRequired());
	}
}

?>