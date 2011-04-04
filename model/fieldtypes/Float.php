<?php
/**
 * Represents a floating point field.
 * 
 * @package sapphire
 * @subpackage model
 */
class Float extends DBField {

	function __construct($name, $defaultVal = 0) {
		$this->defaultVal = is_float($defaultVal) ? $defaultVal : (float) 0;
		
		parent::__construct($name);
	}
	
	function requireField() {
		$parts=Array('datatype'=>'float', 'null'=>'not null', 'default'=>$this->defaultVal, 'arrayValue'=>$this->arrayValue);
		$values=Array('type'=>'float', 'parts'=>$parts);
		DB::requireField($this->tableName, $this->name, $values);
	}
	
	/**
	 * Returns the number, with commas and decimal places as appropriate, eg “1,000.00”.
	 * 
	 * @uses number_format()
	 */
	function Nice() {
		return number_format($this->value, 2);
	}
	
	function Round($precision = 3) {
		return round($this->value, $precision);
	}

	function NiceRound($precision = 3) {
		return number_format(round($this->value, $precision), $precision);
	}
	
	public function scaffoldFormField($title = null) {
		return new NumericField($this->name, $title);
	}

	/**
	 * Returns the value to be set in the database to blank this field.
	 * Usually it's a choice between null, 0, and ''
	 */
	function nullValue() {
		return 0;
	}

	/**
	 * Return an encoding of the given value suitable for inclusion in a SQL statement.
	 * If necessary, this should include quotes.
	 */
	function prepValueForDB($value) {
		if($value === true) {
			return 1;
		}
		if(!$value || !is_numeric($value)) {
			if(strpos($value, '[') === false) {
				return '0';
			} else {
				return Convert::raw2sql($value);
			}
		} else {
			return Convert::raw2sql($value);
		}
	}

}