<?php

/**
 * @package sapphire
 * @subpackage model
 */

/**
 * Represents a boolean field.
 * @package sapphire
 * @subpackage model
 */
class Boolean extends DBField {
	
	function __construct($name, $defaultVal = 0) {
		$this->defaultVal = ($defaultVal) ? 1 : 0;
		
		parent::__construct($name);
	}
	
	function requireField() {
		DB::requireField($this->tableName, $this->name, "tinyint(1) unsigned not null default '{$this->defaultVal}'");
	}
	
	function nullValue() {
		return 0;
	}
	
	function Nice() {
		return ($this->value) ? "yes" : "no";
	}

	/**
	 * Saves this field to the given data object.
	 */
	function saveInto($dataObject) {
		$fieldName = $this->name;
		if($fieldName) {
			$dataObject->$fieldName = $this->value ? 1 : 0;
		} else {
			user_error("DBField::saveInto() Called on a nameless '$this->class' object", E_USER_ERROR);
		}
	}
}

?>