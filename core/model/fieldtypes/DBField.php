<?php

/**
 * @package sapphire
 * @subpackage model
 */

/**
 * Single field in the database.
 * Every field from the database is represented as a sub-class of DBField.  In addition to supporting
 * the creation of the field in the database,
 * @package sapphire
 * @subpackage model
 */
abstract class DBField extends ViewableData {
	
	protected $value;
	
	protected $tableName;
	
	protected $name;
	
	/**
	 * @var $default mixed Default-value in the database.
	 * Might be overridden on DataObject-level, but still useful for setting defaults on
	 * already existing records after a db-build.
	 */
	protected $defaultVal;
	
	function __construct($name) {
		$this->name = $name;
		
		parent::__construct();
	}
	
	/**
	 * Create a DBField object that's not bound to any particular field.
	 * Useful for accessing the classes behaviour for other parts of your code.
	 */
	static function create($className, $value, $name = null) {
		$dbField = Object::create($className, $name);
		$dbField->setValue($value);
		return $dbField;
	}
	
	function setVal($value) {
		return $this->setValue($value);
	}
	
	function setValue($value) {
		$this->value = $value;
	}
	
	function setTable($tableName) {
		$this->tableName = $tableName;
	}
	
	function forTemplate() {
		return $this->value;
	}

	function HTMLATT() {
		return Convert::raw2htmlatt($this->value);
	}
	function ATT() {
		return Convert::raw2att($this->value);
	}
	
	function RAW() {
		return $this->value;
	}
	
	function JS() {
		return Convert::raw2js($this->value);
	}
	
	function HTML(){
		return Convert::raw2xml($this->value);
	}
	
	function XML(){
		return Convert::raw2xml($this->value);
	}
	
	/**
	 * Returns the value to be set in the database to blank this field.
	 * Usually it's a choice between null, 0, and ''
	 */
	function nullValue() {
		return "null";
	}

	/**
	 * Saves this field to the given data object.
	 */
	function saveInto($dataObject) {
		$fieldName = $this->name;
		if($fieldName) {
			$dataObject->$fieldName = $this->value;
		} else {
			user_error("DBField::saveInto() Called on a nameless '" . get_class($this) . "' object", E_USER_ERROR);
		}
	}
	
	/**
	 * Add the field to the underlying database.
	 */
	abstract function requireField();
	
	function debug() {
		return <<<DBG
<ul>
	<li><b>Name:</b>{$this->name}</li>
	<li><b>Table:</b>{$this->tableName}</li>
	<li><b>Value:</b>{$this->value}</li>
</ul>
DBG;
	}
}
?>