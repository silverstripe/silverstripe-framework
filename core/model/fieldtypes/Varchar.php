<?php
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
		$parts=Array('datatype'=>'varchar', 'precision'=>$this->size, 'character set'=>'utf8', 'collate'=>'utf8_general_ci', 'arrayValue'=>$this->arrayValue);
		$values=Array('type'=>'varchar', 'parts'=>$parts);
		DB::requireField($this->tableName, $this->name, $values);
	}
	
	function hasValue() {
		return ($this->value || $this->value == '0');
	}
	
	/**
	 * Return the first letter of the string followed by a .
	 */
	function Initial() {
		if($this->value) return $this->value[0] . '.';
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
	
	function LimitCharacters($limit = 20, $add = "...") {
		$value = trim($this->value);
		return (strlen($value) > $limit) ? substr($value, 0, $limit) . $add : $value;
	}
}

?>