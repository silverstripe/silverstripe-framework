<?php
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
	
		parent::__construct($name, $precision, $precision);
	}
	
	function Nice() {
		return number_format($this->value * 100, $this->decimalSize - 2) . '%';
	}
}

?>