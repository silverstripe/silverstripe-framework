<?php

/**
 * @package sapphire
 * @subpackage model
 */

/**
 * Represents a short text field.
 * @package sapphire
 * @subpackage model
 */
class Varchar extends DBField {
	protected $size;
	
	function __construct($name, $size = 50) {
		$this->size = $size ? $size : 50;
		parent::__construct($name);
	}
	function requireField() {
		DB::requireField($this->tableName, $this->name, "varchar($this->size) character set utf8 collate utf8_general_ci");
	}
	/**
	 * Return the first letter of the string followed by a .
	 */
	function Initial() {
		if($this->value) return $this->value[0] . '.';
	}
	
	function Attr() {
		return Convert::raw2att($this->value);
	}
	
	/**
	 * Ensure that the given value is an absolute URL.
	 */
	function URL() {
		if(ereg('^[a-zA-Z]+://', $this->value)) return $this->value;
		else return "http://" . $this->value;
	}
	
	function RTF() {
		return str_replace("\n", '\par ', $this->value);
	}
	
	/*function forTemplate() {
		return $this->raw2HTML();
	}*/
	
	function LowerCase() {
		return Convert::raw2xml(strtolower($this->value));
	}
	
	function LimitCharacters($limit = 20, $add = "...") {
		$value = trim($this->value);
		return (strlen($value) > $limit) ? substr($value, 0, $limit) . $add : $value;
	}
}

?>