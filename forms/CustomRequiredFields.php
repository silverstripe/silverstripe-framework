<?php
/**
 * Specify special required fields to be executed as part of form validation
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
                                $formField = $fields->dataFieldByName($fieldName);
				if(is_array($fieldName) && isset($fieldName['php'])){
					eval($fieldName['php']);
				}else if($formField) {
					// if an error is found, the form is returned.
					if(!$data[$fieldName] || preg_match('/^\s*$/', $data[$fieldName])) {
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