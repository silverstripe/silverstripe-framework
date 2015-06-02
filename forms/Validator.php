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
	 * @param Form $form
	 *
	 * @return static
	 */
	public function setForm($form) {
		$this->form = $form;

		return $this;
	}

	/**
	 * Returns any errors there may be.
	 *
	 * @return null|array
	 */
	public function validate() {
		$this->errors = null;

		$this->php($this->form->getData());

		return $this->errors;
	}

	/**
	 * Callback to register an error on a field (Called from implementations of {@link FormField::validate})
	 *
	 * @param string $fieldName name of the field
	 * @param string $message error message to display
	 * @param string $messageType optional parameter, gets loaded into the HTML class attribute in the rendered output.
	 *
	 * See {@link getErrors()} for details.
	 */
	public function validationError($fieldName, $message, $messageType = '') {
		$this->errors[] = array(
			'fieldName' => $fieldName,
			'message' => $message,
			'messageType' => $messageType,
		);
	}

	/**
	 * Returns all errors found by a previous call to {@link validate()}.
	 *
	 * The array contains the following keys for each error:
	 * - 'fieldName': the name of the FormField instance
	 * - 'message': Validation message (optionally localized)
	 * - 'messageType': Arbitrary type of the message which is rendered as a CSS class in the FormField template,
	 *   e.g. <span class="message (type)">. Usually "bad|message|validation|required", which renders differently
	 *   if framework/css/Form.css is included.
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @param string $fieldName
	 * @param array $data
	 */
	public function requireField($fieldName, $data) {
		if(is_array($data[$fieldName]) && count($data[$fieldName])) {
			foreach($data[$fieldName] as $componentKey => $componentValue) {
				if(!strlen($componentValue)) {
					$this->validationError(
						$fieldName,
						sprintf('%s %s is required', $fieldName, $componentKey),
						'required'
					);
				}
			}
		} else if(!strlen($data[$fieldName])) {
			$this->validationError(
				$fieldName,
				sprintf('%s is required', $fieldName),
				'required'
			);
		}
	}

	/**
	 * Returns true if the named field is "required".
	 *
	 * Used by FormField to return a value for FormField::Required(), to do things like show *s on the form template.
	 *
	 * By default, it always returns false.
	 */
	public function fieldIsRequired($fieldName) {
		return false;
	}

	/**
	 * @param array $data
	 *
	 * @return mixed
	 */
	abstract public function php($data);
}
