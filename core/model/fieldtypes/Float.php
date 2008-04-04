<?php

/**
 * @package sapphire
 * @subpackage model
 */

/**
 * Represents a floating point field.
 * @package sapphire
 * @subpackage model
 */
class Float extends DBField {
	
	/**
	 * Allows for setting the digits before decimal point
	 * e.g. a value of 7 can be -99999.99
	 *
	 * @var int
	 */
	protected $countTotalDigits;
	
	/**
	 * Allows for setting the digits before decimal point
	 * e.g. a value of 4 can be -999.9999
	 *
	 * @var int
	 */
	protected $countDigitsAfterDecimal;
	
	function __construct($name, $countTotalDigits, $countDigitsAfterDecimal) {
		$this->countTotalDigits = $countTotalDigits;
		$this->countDigitsAfterDecimal = $countDigitsAfterDecimal;
		
		parent::__construct($name);
	}
	
	function requireField() {
		if($this->countTotalDigits && $this->countDigitsAfterDecimal) {
			$sql = "float({$this->countTotalDigits},{$this->countDigitsAfterDecimal})"; 
		} else {
			$sql = "float";
		}
		DB::requireField($this->tableName, $this->name, $sql);
	}
	
	function Nice() {
		return number_format($this->value, 2);
	}	
}
?>
