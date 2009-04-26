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
		DB::requireField($this->tableName, $this->name, "tinyint(1) unsigned not null default '{$this->defaultVal}'");
	}
	
	function nullValue() {
		return 0;
	}
	
	function Nice() {
		return ($this->value) ? _t('Boolean.YES', 'Yes') : _t('Boolean.NO', 'No');
	}
	
	function NiceAsBoolean() {
		return ($this->value) ? 'true' : 'false';
	}

	/**
	 * Saves this field to the given data object.
	 */
	function saveInto($dataObject) {
		$fieldName = $this->name;
		if($fieldName) {
			$dataObject->$fieldName = ($this->value) ? 1 : 0;
		} else {
			user_error("DBField::saveInto() Called on a nameless '$this->class' object", E_USER_ERROR);
		}
	}
	
	public function scaffoldFormField($title = null, $params = null) {
		return new CheckboxField($this->name, $title);
	}
	
	public function scaffoldSearchField($title = null) {
		$anyText = _t('Boolean.ANY', 'Any');
		$source = array(
			1 => _t('Boolean.YES', 'Yes'),
			0 => _t('Boolean.NO', 'No')
		);
		
		return new DropdownField($this->name, $title, $source, '', null, "($anyText)");
	}

	/**
	 * Return an encoding of the given value suitable for inclusion in a SQL statement.
	 * If necessary, this should include quotes.
	 */
	function prepValueForDB($value) {
		if($value === true) {
			return 1;
		} if(!$value || !is_numeric($value)) {
			return "0";
		} else {
			return addslashes($value);
		}
	}
	
}

?>