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
		if($message) $this->error($message);
		parent::__construct();
	}
	
	/**
	 * Return the full error meta-data, suitable for combining with another ValidationResult.
	 */
	function getErrorMetaData() {
		return $this->errorList;
	}
	
	/**
	 * Record an error against this validation result.
	 * 
	 * It's better to use addError, addFeildError, addMessage, or addFieldMessage instead.
	 * 
	 * @param string $message     The message string.
	 * @param string $code        A codename for this error. Only one message per codename will be added.
	 *                            This can be usedful for ensuring no duplicate messages
	 * @param string $fieldName   The field to link the message to.  If omitted; a form-wide message is assumed.
	 * @param string $messageType The type of message: e.g. "bad", "warning", "good", or "required". Passed as a CSS
	 *                            class to the form, so other values can be used if desired.
	 *
	 * @deprecated 3.2
	 */
	public function error($message, $code = null, $fieldName = null, $messageType = "bad") {
		Deprecation::notice('3.2', 'Use addError or addFieldError instead.');

		return $this->addFieldError($fieldName, $message, $messageType, $code);
	}

	/**
	 * Record an error against this validation result,
	 * 
	 * @param string $message     The message string.
	 * @param string $messageType The type of message: e.g. "bad", "warning", "good", or "required". Passed as a CSS
	 *                            class to the form, so other values can be used if desired.
	 * @param string $code        A codename for this error. Only one message per codename will be added.
	 *                            This can be usedful for ensuring no duplicate messages
	 */
	public function addError($message, $messageType = "bad", $code = null) {
		$this->isValid = false;
		
		return $this->addFieldMessage(null, $message, $messageType, $code);
	}

	/**
	 * Record an error against this validation result,
	 * 
	 * @param string $fieldName   The field to link the message to.  If omitted; a form-wide message is assumed.
	 * @param string $message     The message string.
	 * @param string $messageType The type of message: e.g. "bad", "warning", "good", or "required". Passed as a CSS
	 *                            class to the form, so other values can be used if desired.
	 * @param string $code        A codename for this error. Only one message per codename will be added.
	 *                            This can be usedful for ensuring no duplicate messages
	 */
	public function addFieldError($fieldName = null, $message, $messageType = "bad", $code = null) {
		$this->isValid = false;
		
		return $this->addFieldMessage($fieldName, $message, $messageType, $code);
	}

	/**
	 * Add a message to this ValidationResult without necessarily marking it as an error
	 *  
	 * @param string $message     The message string.
	 * @param string $messageType The type of message: e.g. "bad", "warning", "good", or "required". Passed as a CSS
	 *                            class to the form, so other values can be used if desired.
	 * @param string $code        A codename for this error. Only one message per codename will be added.
	 *                            This can be usedful for ensuring no duplicate messages
	 */
	public function addMessage($message, $messageType = "bad", $code = null) {
		return $this->addFieldMessage(null, $message, $messageType, $code);
	}

	/**
	 * Add a message to this ValidationResult without necessarily marking it as an error
	 *  
	 * @param string $fieldName   The field to link the message to.  If omitted; a form-wide message is assumed.
	 * @param string $message     The message string.
	 * @param string $messageType The type of message: e.g. "bad", "warning", "good", or "required". Passed as a CSS
	 *                            class to the form, so other values can be used if desired.
	 * @param string $code        A codename for this error. Only one message per codename will be added.
	 *                            This can be usedful for ensuring no duplicate messages
	 */
	public function addFieldMessage($fieldName, $message, $messageType = "bad", $code = null) {
		$metadata = array(
			'message' => $message,
			'fieldName' => $fieldName,
			'messageType' => $messageType,
		);
		
		if($code) {
			if(!is_numeric($code)) {
				$this->errorList[$code] = $metadata;
			} else {
				throw new InvalidArgumentException(
					"ValidationResult::error() - Don't use a numeric code '$code'.  Use a string.");
				$this->errorList[$code] = $metadata;
			}
		} else {
			$this->errorList[] = $metadata;
		}
	}
	
	/**
	 * Returns true if the result is valid.
	 */
	public function valid() {
		return $this->isValid;
	}
	
	/**
	 * Get an array of errors
	 */
	public function messageList() {
		$list = array();
		foreach($this->errorList as $key => $item) {
			if(is_numeric($key)) $list[] = $item['message'];
			else $list[$key] = $item['message'];
		}
		return $list;
	}
	
	/**
	 * Get the field-specific messages as a map.
	 * Keys will be field names, and values will be a 2 element map with keys 'messsage', and 'messageType'
	 */
	public function fieldErrors() {
		$output = array();
		foreach($this->errorList as $key => $item) {
			if($item['fieldName']) {
				$output[$item['fieldName']] = array(
					'message' => $item['message'],
					'messageType' => $item['messageType']
				);
			}
		}
		return $output;
	}
	
	/**
	 * Get an array of error codes
	 */
	public function codeList() {
		$codeList = array();
		foreach($this->errorList as $k => $v) if(!is_numeric($k)) $codeList[] = $k;
		return $codeList;
	}
	
	/**
	 * Get the error message as a string.
	 */
	public function message() {
		return implode("; ", $this->messageList());
	}
	
	/**
	 * The the error message that's not related to a field as a string
	 */
	public function overallMessage() {
		$messages = array();
		foreach($this->errorList as $item) {
			if(!$item['fieldName']) $messages[] = $item['message'];
		}
		return implode("; ", $messages);
	}
	
	/**
	 * Get a starred list of all messages
	 */
	public function starredList() {
		return " * " . implode("\n * ", $this->messageList());
	}
	
	/**
	 * Combine this Validation Result with the ValidationResult given in other.
	 * It will be valid if both this and the other result are valid.
	 * This object will be modified to contain the new validation information.
	 */
	public function combineAnd(ValidationResult $other) {
		$this->isValid = $this->isValid && $other->valid();
		$this->errorList = array_merge($this->errorList, $other->getErrorMetaData());
	}
}
