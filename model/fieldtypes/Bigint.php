<?php
/**
 * Represents a signed 8 byte integer field. Do note PHP running as 32-bit might not work with Bigint properly, as it
 * would convert the value to a float when queried from the database since the value is a 64-bit one.
 *
 * @package framework
 * @subpackage model
 * @see Int
 */
class Bigint extends Int {

	function __construct($name, $defaultVal = 0) {
		$this->defaultVal = is_int($defaultVal) ? $defaultVal : 0;

		parent::__construct($name);
	}

	function requireField() {
		$parts=Array('datatype'=>'bigint', 'precision'=>20, 'null'=>'not null', 'default'=>$this->defaultVal,
		             'arrayValue'=>$this->arrayValue);
		$values=Array('type'=>'bigint', 'parts'=>$parts);
		DB::requireField($this->tableName, $this->name, $values);
	}
}
