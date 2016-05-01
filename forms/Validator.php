<?php

/**
 * This validation class handles all form and custom form validation through the use of Required
 * fields. It relies on javascript for client-side validation, and marking fields after server-side
 * validation. It acts as a visitor to individual form fields.
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
	 * @return $this
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
	 * Callback to register an error on a field (Called from implementations of
	 * {@link FormField::validate}). The optional error message type parameter is loaded into the
	 * HTML class attribute.
	 *
	 * See {@link getErrors()} for details.
	 *
	 * @param string $fieldName
	 * @param string $errorMessage
	 * @param string $errorMessageType
	 */
	public function validationError($fieldName, $errorMessage, $errorMessageType = '') {
		$this->errors[] = array(
			'fieldName' => $fieldName,
			'message' => $errorMessage,
			'messageType' => $errorMessageType,
		);
	}

	/**
	 * Returns all errors found by a previous call to {@link validate()}. The returned array has a
	 * structure resembling:
	 *
	 * <code>
	 *     array(
	 *         'fieldName' => '[form field name]',
	 *         'message' => '[validation error message]',
	 *         'messageType' => '[bad|message|validation|required]',
	 *     )
	 * </code>
	 *
	 * @return null|array
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
	 * Returns whether the field in question is required. This will usually display '*' next to the
	 * field. The base implementation always returns false.
	 *
	 * @param string $fieldName
	 *
	 * @return bool
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
