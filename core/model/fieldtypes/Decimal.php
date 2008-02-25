<?php
/**
 * Decimal value.
 */
class Decimal extends DBField {
	protected $wholeSize, $decimalSize;
	
	/**
	 * Create a new Decimal field.
	 */
	function __construct($name, $wholeSize = 9, $decimalSize = 2) {
		$this->wholeSize = isset($wholeSize) ? $wholeSize : 9;
		$this->decimalSize = isset($decimalSize) ? $decimalSize : 2;
		parent::__construct($name);
	}
	
	function Nice() {
		return number_format($this->value,$this->decimalSize);
	}
	
	function Int() {
		return floor( $this->value );
	}
	
	function requireField() {
		DB::requireField($this->tableName, $this->name, "decimal($this->wholeSize,$this->decimalSize)");
	}	
}

?>