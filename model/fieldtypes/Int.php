<?php
/**
 * Represents a signed 32 bit integer field.
 * 
 * @package framework
 * @subpackage model
 */
class Int extends DBField {

	public function __construct($name = null, $defaultVal = 0) {
		$this->defaultVal = is_int($defaultVal) ? $defaultVal : 0;
		
		parent::__construct($name);
	}

	/**
	 * Returns the number, with commas added as appropriate, eg “1,000”.
	 */
	public function Formatted() {
		return number_format($this->value);
	}

	public function nullValue() {
		return "0";
	}

	public function requireField() {
		$parts=Array(
			'datatype'=>'int',
			'precision'=>11,
			'null'=>'not null',
			'default'=>$this->defaultVal,
			'arrayValue'=>$this->arrayValue);
		
		$values=Array('type'=>'int', 'parts'=>$parts);
		DB::requireField($this->tableName, $this->name, $values);
	}

	public function Times() {
		$output = new ArrayList();
		for( $i = 0; $i < $this->value; $i++ )
			$output->push( new ArrayData( array( 'Number' => $i + 1 ) ) );

		return $output;
	}

	public function Nice() {
		return sprintf( '%d', $this->value );
	}
	
	public function scaffoldFormField($title = null, $params = null) {
		return new NumericField($this->name, $title);
	}
	
	/**
	 * Return an encoding of the given value suitable for inclusion in a SQL statement.
	 * If necessary, this should include quotes.
	 */
	public function prepValueForDB($value) {
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

