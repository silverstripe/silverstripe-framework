<?php

/**
 * A class that combined as a boolean result with an optional list of error messages.
 * This is used for returning validation results from validators
 * @package framework
 * @subpackage core
 */
class ValidationResult extends Object {
	/**
	 * Boolean - is the result valid or not
	 */
	protected $isValid;


	/**
	 * Array of errors
	 */
	protected $errorList = array();

	/**
	 * Create a new ValidationResult.
	 * By default, it is a successful result.	Call $this->error() to record errors.
	 */
	public function __construct($valid = true, $message = null) {
		$this->isValid = $valid;
		if($message) $this->errorList[] = $message;
		parent::__construct();
	}

	/**
	 * Record an error against this validation result,
	 * @param $message The validation error message
	 * @param $code An optional error code string, that can be accessed with {@link $this->codeList()}.
	 * @return ValidationResult this
	 */
	public function error($message, $code = null) {
		$this->isValid = false;

		if($code) {
			if(!is_numeric($code)) {
				$this->errorList[$code] = $message;
			} else {
				user_error("ValidationResult::error() - Don't use a numeric code '$code'.  Use a string."
					. "I'm going to ignore it.", E_USER_WARNING);
				$this->errorList[$code] = $message;
			}
		} else {
			$this->errorList[] = $message;
		}

		return $this;
	}

	/**
	 * Returns true if the result is valid.
	 * @return boolean
	 */
	public function valid() {
		return $this->isValid;
	}

	/**
	 * Get an array of errors
	 * @return array
	 */
	public function messageList() {
		return $this->errorList;
	}

	/**
	 * Get an array of error codes
	 * @return array
	 */
	public function codeList() {
		$codeList = array();
		foreach($this->errorList as $k => $v) if(!is_numeric($k)) $codeList[] = $k;
		return $codeList;
	}

	/**
	 * Get the error message as a string.
	 * @return string
	 */
	public function message() {
		return implode("; ", $this->errorList);
	}

	/**
	 * Get a starred list of all messages
	 * @return string
	 */
	public function starredList() {
		return " * " . implode("\n * ", $this->errorList);
	}

	/**
	 * Combine this Validation Result with the ValidationResult given in other.
	 * It will be valid if both this and the other result are valid.
	 * This object will be modified to contain the new validation information.
	 *
	 * @param ValidationResult the validation result object to combine
	 * @return ValidationResult this
	 */
	public function combineAnd(ValidationResult $other) {
		$this->isValid = $this->isValid && $other->valid();
		$this->errorList = array_merge($this->errorList, $other->messageList());

		return $this;
	}


}
