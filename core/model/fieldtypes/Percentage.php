<?php

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