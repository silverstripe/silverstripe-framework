<?php
/**
 * Required Fields allows you to set which fields
 * need to be present before submitting the form
 * Submit an array of arguments or each field as a
 * seperate argument. Validation is performed on a name by
 * name basis.
 *
 * @package forms
 * @subpackage validators
 */
class RequiredFields extends Validator {

	protected $required;
	protected $useLabels = true;

	/**
	 * Pass each field to be validated as a seperate argument
	 * to the constructor of this object. (an array of elements are ok)
	 */
	function __construct() {
		$Required = func_get_args();
		if( isset($Required[0]) && is_array( $Required[0] ) )
			$Required = $Required[0];
		$this->required = $Required;

		parent::__construct();
	}

	public function useLabels($flag) {
		$this->useLabels = $flag;
	}

	/**
	 * Clears all the validation from this object.
	 */
	public function removeValidation(){
		$this->required = null;
	}

	/**
	 * Debug helper
	 */
	function debug() {
	 if(!is_array($this->required)) return false;

	 $result = "<ul>";
	 foreach( $this->required as $name ){
	 	$result .= "<li>$name</li>";
	 }

	 $result .= "</ul>";
	 return $result;
	}

	/**
	* Allows validation of fields via specification of a php function for validation which is executed after
	* the form is submitted
	*/
	function php($data) {
		$valid = true;

		$fields = $this->form->Fields();
		foreach($fields as $field) {
			$valid = ($field->validate($this) && $valid);
		}
		if($this->required) {
			foreach($this->required as $fieldName) {
				if(!$fieldName) continue;

				$formField = $fields->dataFieldByName($fieldName);

				$error = true;
				// submitted data for file upload fields come back as an array
				$value = isset($data[$fieldName]) ? $data[$fieldName] : null;
				if(is_array($value)) {
					if ($formField instanceof FileField && isset($value['error']) && $value['error']) {
						$error = true;
					} else {
						$error = (count($value)) ? false : true;
					}
				} else {
					// assume a string or integer
					$error = (strlen($value)) ? false : true;
				}

				if($formField && $error) {
					$errorMessage = sprintf(_t('Form.FIELDISREQUIRED', '%s is required'), strip_tags('"' . ($formField->Title() ? $formField->Title() : $fieldName) . '"'));
					if($msg = $formField->getCustomValidationMessage()) {
						$errorMessage = $msg;
					}
					$this->validationError(
						$fieldName,
						$errorMessage,
						"required"
					);
					$valid = false;
				}
			}
		}

		return $valid;
	}

	/**
	 * Add's a single required field to requiredfields stack
	 */
	function addRequiredField( $field ) {
		$this->required[] = $field;
	}

	function removeRequiredField($field) {
		for($i=0; $i<count($this->required); $i++) {
			if($field == $this->required[$i]) {
				unset($this->required[$i]);
			}
		}
	}

	/**
	 * allows you too add more required fields to this object after construction.
	 */
	function appendRequiredFields($requiredFields){
		$this->required = array_merge($this->required,$requiredFields->getRequired());
	}

	/**
	 * Returns true if the named field is "required".
	 * Used by FormField to return a value for FormField::Required(), to do things like show *s on the form template.
	 */
	function fieldIsRequired($fieldName) {
		return in_array($fieldName, $this->required);
	}

	/**
	 * getter function for append
	 */
	function getRequired(){
		return $this->required;
	}
}


