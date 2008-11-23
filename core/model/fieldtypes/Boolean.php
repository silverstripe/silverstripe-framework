<?php
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
		$parts=Array('datatype'=>'tinyint', 'precision'=>1, 'sign'=>'unsigned', 'null'=>'not null', 'default'=>$this->defaultVal);
		$values=Array('type'=>'boolean', 'parts'=>$parts);
		DB::requireField($this->tableName, $this->name, $values);
	}
	
	function Nice() {
		return ($this->value) ? "yes" : "no";
	}
	
	function NiceAsBoolean() {
		return ($this->value) ? "true" : "false";
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

	public function scaffoldFormField($title = null, $params = null) {
		return new CheckboxField($this->name, $title);
	}

	/**
	 * Return an encoding of the given value suitable for inclusion in a SQL statement.
	 * If necessary, this should include quotes.
	 */
	function prepValueForDB($value) {
		if($value === true || $value === 1) {
			return "'1'";
		} if(!$value || !is_numeric($value)) {
			return "'0'";
		} else {
			return addslashes($value);
		}
	}

	function nullValue() {
		return "'0'";
	}
	
}

?>
