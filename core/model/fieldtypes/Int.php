<?php

/**
 * @package sapphire
 * @subpackage model
 */

/**
 * Represents an integer field.
 * @package sapphire
 * @subpackage model
 */
class Int extends DBField {

	function __construct($name, $defaultVal = 0) {
		$this->defaultVal = is_int($defaultVal)
			? $defaultVal
			: 0;

		parent::__construct($name);
	}

	function Formatted() {
		return number_format($this->value);
	}

	function nullValue() {
		return "0";
	}

	function requireField() {
		DB::requireField($this->tableName, $this->name, "int(11) not null default '{$this->defaultVal}'");
	}

	function Times() {
		$output = new DataObjectSet();
		for( $i = 0; $i < $this->value; $i++ )
			$output->push( new ArrayData( array( 'Number' => $i + 1 ) ) );

		return $output;
	}

	function Nice() {
		return sprintf( '%d', $this->value );
	}
	
	
	
}

?>
