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
	 * @var {@link ValidationResult} or string
	 */
	protected $result;

	public function __construct($result = null, $message = null, $code = 0) {
		if($result instanceof ValidationResult) {
			$this->result = $result;
		} else {
			$code = $message;
			$message = $result;
		}
		
		parent::__construct($message, $code);
	}
	
	public function getResult() {
		return $this->result;	
	}
}
