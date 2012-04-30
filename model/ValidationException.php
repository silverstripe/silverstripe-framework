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
	public function __construct($result = null, $code = 0, $dummy = null) {
		$exceptionMessage = null;

		// Backwards compatibiliy failover.  The 2nd argument used to be $message, and $code the 3rd.
		// For callers using that, we ditch the message
		if(!is_numeric($code)) {
			$exceptionMessage = $code;
			if($dummy) $code = $dummy;
		}

		if($result instanceof ValidationResult) {
			$this->result = $result;

		} else if(is_string($result)) {
			$this->result = new ValidationResult(false, $result);

		} else if(!$result) {
			$this->result = new ValidationResult(false, _t("ValdiationExcetpion.DEFAULT_ERROR", "Validation error"));

		} else {
			throw new InvalidArgumentException("ValidationExceptions must be passed a ValdiationResult, a string, or nothing at all");
		}
		
		// Construct
		parent::__construct($exceptionMessage ? $exceptionMessage : $this->result->message(), $code);
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
