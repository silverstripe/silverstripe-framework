<?php
/**
 * This validation class handles all form and custom form validation through
 * the use of Required fields.
 * 
 * Relies on javascript for client-side validation, and marking fields after serverside validation.
 * 
 * Acts as a visitor to individual form fields.
 * 
 * @package forms
 * @subpackage validators
 */ 
abstract class Validator extends Object {
	
	/**
	 * @var Form $form
	 */
	protected $form;
	
	/**
	 * @var array $errors
	 */
	protected $errors;

	/**
	 * @deprecated 3.0 Use custom javascript validation instead
	 */
	public static function set_javascript_validation_handler($handler) {
		Deprecation::notice('3.0', 'Use custom javascript validation instead.');
	}

	/**
	 * @deprecated 3.0 Use custom javascript validation instead
	 */
	public static function get_javascript_validator_handler() {
		Deprecation::notice('3.0', 'Use custom javascript validation instead.');
	}

	/**
	 * @deprecated 3.0 Use custom javascript validation instead
	 */
	public function setJavascriptValidationHandler($handler) {
		Deprecation::notice('3.0', 'Use custom javascript validation instead.');
	}

	/**
	 * Gets the current javascript validation handler for this form.
	 * If not set, falls back to the global static {@link self::$javascript_validation_handler}.
	 * 
	 * @return string
	 */
	public function getJavascriptValidationHandler() {
		Deprecation::notice('3.0', 'Use custom javascript validation instead.');
	}

	/**
	 * @param Form $form
	 */
	function setForm($form) {
		$this->form = $form;
		return $this;
	}
	
	/**
	 * @return array Errors (if any)
	 */
	function validate(){
		$this->errors = null;
		$this->php($this->form->getData());
		return $this->errors;
	}
	
	/**
	 * Callback to register an error on a field (Called from implementations of {@link FormField::validate})
	 * 
	 * @param $fieldName name of the field
	 * @param $message error message to display
	 * @param $messageType optional parameter, gets loaded into the HTML class attribute in the rendered output. See {@link getErrors()} for details.
	 */
	function validationError($fieldName, $message, $messageType='') {
		$this->errors[] = array(
			'fieldName' => $fieldName,
			'message' => $message,
			'messageType' => $messageType,
		);
	}
	
	/**
	 * Returns all errors found by a previous call to {@link validate()}.
	 * The array contains the following keys for each error:
	 * - 'fieldName': the name of the FormField instance
	 * - 'message': Validation message (optionally localized)
	 * - 'messageType': Arbitrary type of the message which is rendered as a CSS class in the FormField template,
	 *   e.g. <span class="message (type)">. Usually "bad|message|validation|required", which renders differently
	 *   if framework/css/Form.css is included.
	 * 
	 * @return array
	 */
	function getErrors() {
		return $this->errors;
	}
	
	function requireField($fieldName, $data) {
		if(is_array($data[$fieldName]) && count($data[$fieldName])) {
			foreach($data[$fieldName] as $componentkey => $componentVal){
				if(!strlen($componentVal)) $this->validationError($fieldName, "$fieldName $componentkey is required", "required");
			}
			
		}else if(!strlen($data[$fieldName])) $this->validationError($fieldName, "$fieldName is required", "required");
	}
	
	/**
	 * Returns true if the named field is "required".
	 * Used by FormField to return a value for FormField::Required(), to do things like show *s on the form template.
	 * By default, it always returns false.
	 */
	function fieldIsRequired($fieldName) {
		return false;
	}
	
	abstract function php($data);
}

