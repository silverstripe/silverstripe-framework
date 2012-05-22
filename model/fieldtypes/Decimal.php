<?php
/**
 * Represents a Decimal field.
 * @package framework
 * @subpackage model
 */
class Decimal extends DBField {
	protected $wholeSize, $decimalSize, $defaultValue;
	
	/**
	 * Create a new Decimal field.
	 */
	function __construct($name = null, $wholeSize = 9, $decimalSize = 2, $defaultValue = 0) {
		$this->wholeSize = isset($wholeSize) ? $wholeSize : 9;
		$this->decimalSize = isset($decimalSize) ? $decimalSize : 2;
		$this->defaultValue = $defaultValue;
		parent::__construct($name);
	}
	
	function Nice() {
		return number_format($this->value,$this->decimalSize);
	}
	
	function Int() {
		return floor( $this->value );
	}
	
	function requireField() {
		$parts=Array('datatype'=>'decimal', 'precision'=>"$this->wholeSize,$this->decimalSize", 'default'=>(double)$this->defaultValue, 'arrayValue'=>$this->arrayValue);
		$values=Array('type'=>'decimal', 'parts'=>$parts);
		DB::requireField($this->tableName, $this->name, $values);
	}
	
	function saveInto($dataObject) {
		$fieldName = $this->name;
		if($fieldName) {
			$dataObject->$fieldName = (float)preg_replace('/[^0-9.\-\+]/', '', $this->value);
		} else {
			user_error("DBField::saveInto() Called on a nameless '" . get_class($this) . "' object", E_USER_ERROR);
		}
	}
	
	public function scaffoldFormField($title = null, $params = null) {
		return new NumericField($this->name, $title);
	}
		
	public function nullValue() {
		return "0.00";
	}

	/**
	 * Return an encoding of the given value suitable for inclusion in a SQL statement.
	 * If necessary, this should include quotes.
	 */
	function prepValueForDB($value) {
		if($value === true) {
			return 1;
		} if(!$value || !is_numeric($value)) {
			if(strpos($value, '[')===false)
				return '0';
			else
				return Convert::raw2sql($value);
		} else {
			return Convert::raw2sql($value);
		}
	}
	
}


