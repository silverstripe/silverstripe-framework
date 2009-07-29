<?php

/**
 * @package sapphire
 * @subpackage model
 */

/**
 * Represents a decimal field from 0-1 containing a percentage value.
 * @package sapphire
 * @subpackage model
 */
class Percentage extends Decimal {
	
	/**
	 * Create a new Decimal field.
	 */
	function __construct($name, $precision = 4) {
	
		if( !$precision )
			$precision = 4;	
	
		parent::__construct($name, $precision + 1, $precision);
	}
	
	function Nice() {
		return number_format($this->value * 100, $this->decimalSize - 2) . '%';
	}
	
	function saveInto($dataObject) {
		parent::saveInto($dataObject);
		
		$fieldName = $this->name;
		if($fieldName && $dataObject->$fieldName > 1.0) {
			$dataObject->$fieldName = 1.0;
		}
	}	
}

?>
