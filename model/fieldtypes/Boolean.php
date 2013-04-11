<?php
/**
 * Represents a boolean field.
 * 
 * @package framework
 * @subpackage model
 */
class Boolean extends DBField {
	
	public function __construct($name = null, $defaultVal = 0) {
		$this->defaultVal = ($defaultVal) ? 1 : 0;
		
		parent::__construct($name);
	}
	
	public function requireField() {
		$parts=Array(
			'datatype'=>'tinyint',
			'precision'=>1,
			'sign'=>'unsigned',
			'null'=>'not null',
			'default'=>$this->defaultVal,
			'arrayValue'=>$this->arrayValue
		);
		$values=Array('type'=>'boolean', 'parts'=>$parts);
		DB::requireField($this->tableName, $this->name, $values);
	}
	
	public function Nice() {
		return ($this->value) ? _t('Boolean.YESANSWER', 'Yes') : _t('Boolean.NOANSWER', 'No');
	}
	
	public function NiceAsBoolean() {
		return ($this->value) ? 'true' : 'false';
	}

	/**
	 * Saves this field to the given data object.
	 */
	public function saveInto($dataObject) {
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
			1 => _t('Boolean.YESANSWER', 'Yes'),
			0 => _t('Boolean.NOANSWER', 'No')
		);
		
		$field = new DropdownField($this->name, $title, $source);
		$field->setEmptyString("($anyText)");
		return $field;
	}

	/**
	 * Return an encoding of the given value suitable for inclusion in a SQL statement.
	 * If necessary, this should include quotes.
	 */
	public function prepValueForDB($value) {
		if(strpos($value, '[')!==false)
			return Convert::raw2sql($value);
		else {
			if($value && strtolower($value) != 'f') {
				return "'1'";
			} else {
				return "'0'";
			}
		}
	}

	public function nullValue() {
		return "'0'";
	}
	
}


