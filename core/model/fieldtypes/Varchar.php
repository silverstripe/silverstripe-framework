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
		DB::requireField($this->tableName, $this->name, "varchar($this->size) character set utf8 collate utf8_general_ci");
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
	 * @deprecated 2.3 Use ATT_val()
	 */
	function Attr() {
		user_error("Varchar::Attr() is deprecated.  Use ATT_val() instead.", E_USER_NOTICE);
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
	
	function LimitCharacters($limit = 20, $add = "...") {
		$value = trim($this->value);
		return (strlen($value) > $limit) ? substr($value, 0, $limit) . $add : $value;
	}
}

?>