<?php

/**
 * Required Fields allows you to set which fields need to be present before
 * submitting the form. Submit an array of arguments or each field as a separate
 * argument.
 *
 * Validation is performed on a field by field basis through
 * {@link FormField::validate}.
 *
 * @package forms
 * @subpackage validators
 */
class RequiredFields extends Validator {

	protected $required;
	protected $useLabels = true;

	/**
	 * Pass each field to be validated as a seperate argument to the constructor
	 * of this object. (an array of elements are ok).
	 */
	public function __construct() {
		$required = func_get_args();
		if(isset($required[0]) && is_array($required[0])) {
			$required = $required[0];
		}
		if(!empty($required)) {
			$this->required = ArrayLib::valuekey($required);
		} else {
			$this->required = array();
		}

		parent::__construct();
	}

	/**
	 * @deprecated since version 4.0
	 */
	public function useLabels($flag) {
		Deprecation::notice('4.0', 'useLabels will be removed from 4.0, please do not use it or implement it yourself');
		$this->useLabels = $flag;
		return $this;
	}

	/**
	 * Clears all the validation from this object.
	 *
	 * @return RequiredFields
	 */
	public function removeValidation() {
		$this->required = array();

		return $this;
	}

	/**
	 * Debug helper
	 */
	public function debug() {
		if(!is_array($this->required)) {
			return false;
		}

		$result = "<ul>";
		foreach( $this->required as $name ){
			$result .= "<li>$name</li>";
		}

		$result .= "</ul>";
		return $result;
	}

	/**
	 * Allows validation of fields via specification of a php function for
	 * validation which is executed after the form is submitted.
	 *
	 * @param array $data
	 *
	 * @return boolean
	 */
	public function php($data) {
		$valid = true;
		$fields = $this->form->Fields();

		foreach($fields as $field) {
			$valid = ($field->validate($this) && $valid);
		}

		if($this->required) {
			foreach($this->required as $fieldName) {
				if(!$fieldName) {
					continue;
				}

				if($fieldName instanceof FormField) {
					$formField = $fieldName;
					$fieldName = $fieldName->getName();
				}
				else {
					$formField = $fields->dataFieldByName($fieldName);
				}

				$error = true;

				// submitted data for file upload fields come back as an array
				$value = isset($data[$fieldName]) ? $data[$fieldName] : null;

				if(is_array($value)) {
					if($formField instanceof FileField && isset($value['error']) && $value['error']) {
						$error = true;
					} else {
						$error = (count($value)) ? false : true;
					}
				} else {
					// assume a string or integer
					$error = (strlen($value)) ? false : true;
				}

				if($formField && $error) {
					$errorMessage = _t(
						'Form.FIELDISREQUIRED',
						'{name} is required',
						array(
							'name' => strip_tags(
								'"' . ($formField->Title() ? $formField->Title() : $fieldName) . '"'
							)
						)
					);

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
	 * Adds a single required field to required fields stack.
	 *
	 * @param string $field
	 *
	 * @return RequiredFields
	 */
	public function addRequiredField($field) {
		$this->required[$field] = $field;

		return $this;
	}

	/**
	 * Removes a required field
	 *
	 * @param string $field
	 *
	 * @return RequiredFields
	 */
	public function removeRequiredField($field) {
		unset($this->required[$field]);

		return $this;
	}

	/**
	 * Add {@link RequiredField} objects together
	 *
	 * @param RequiredFields
	 *
	 * @return RequiredFields
	 */
	public function appendRequiredFields($requiredFields) {
		$this->required = $this->required + ArrayLib::valuekey(
			$requiredFields->getRequired()
		);

		return $this;
	}

	/**
	 * Returns true if the named field is "required".
	 *
	 * Used by {@link FormField} to return a value for FormField::Required(),
	 * to do things like show *s on the form template.
	 *
	 * @param string $fieldName
	 *
	 * @return boolean
	 */
	public function fieldIsRequired($fieldName) {
		return isset($this->required[$fieldName]);
	}

	/**
	 * Return the required fields
	 *
	 * @return array
	 */
	public function getRequired() {
		return array_values($this->required);
	}
}
