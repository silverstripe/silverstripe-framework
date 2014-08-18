<?php
/**
 * Exception thrown by {@link DataObject}::write if validation fails. By throwing an
 * exception rather than a user error, the exception can be caught in unit tests and as such
 * can be used as a successful test.
 *
 * @package framework
 * @subpackage validation
 */
class ValidationException extends Exception {

	/**
	 * The contained ValidationResult related to this error
	 *
	 * @var ValidationResult
	 */
	protected $result;

	/**
	 * Construct a new ValidationException with an optional ValidationResult object
	 *
	 * @param ValidationResult|string $result The ValidationResult containing the
	 * failed result. Can be substituted with an error message instead if no
	 * ValidationResult exists.
	 * @param string|integer $message The error message. If $result was given the
	 * message string rather than a ValidationResult object then this will have
	 * the error code number.
	 * @param integer $code The error code number, if not given in the second parameter
	 */
	public function __construct($result = null, $message = null, $code = 0) {

		// Check arguments
		if(!($result instanceof ValidationResult)) {

			// Shift parameters if no ValidationResult is given
			$code = $message;
			$message = $result;

			// Infer ValidationResult from parameters
			$result = new ValidationResult(false, $message);
		} elseif(empty($message)) {

			// Infer message if not given
			$message = $result->message();
		}

		// Construct
		$this->result = $result;
		parent::__construct($message, $code);
	}

	/**
	 * Retrieves the ValidationResult related to this error
	 *
	 * @return ValidationResult
	 */
	public function getResult() {
		return $this->result;
	}
}
