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
		DB::require_field($this->tableName, $this->name, $values);
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

	public function nullValue() {
		return 0;
	}

	public function prepValueForDB($value) {
		if(is_bool($value)) {
			return $value ? 1 : 0;
		} else if(empty($value)) {
			return 0;
		} else if(is_string($value)){
			switch(strtolower($value)) {
				case 'false':
				case 'f':
					return 0;
				case 'true':
				case 't':
					return 1;
			}
		}
		return $value ? 1 : 0;
	}
}


