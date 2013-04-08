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
	 *
	 * @return Text
	 */
	public function Formatted() {
		return DBField::create_field('Text', number_format($this->value));
	}

	/**
	 * @return string
	 */
	public function nullValue() {
		return "0";
	}

	public function requireField() {
		$parts = array(
			'datatype' => 'int',
			'precision' => 11,
			'null' => 'not null',
			'default' => $this->defaultVal,
			'arrayValue' => $this->arrayValue
		);
		
		$values = array(
			'type' => 'int', 
			'parts' => $parts
		);

		DB::requireField($this->tableName, $this->name, $values);
	}

	/**
	 * @return ArrayList
	 */
	public function Times() {
		$output = new ArrayList();

		for($i = 0; $i < $this->value; $i++) {
			$output->push(new ArrayData(array(
				'Number' => $i + 1 
			)));
		}

		return $output;
	}

	/**
	 * @return Text
	 */
	public function Nice() {
		return DBField::create_field('Text', sprintf( '%d', $this->value));
	}
	
	/**
	 * @param string $title
	 * @param array $params
	 *
	 * @return NumericField
	 */
	public function scaffoldFormField($title = null, $params = null) {
		return new NumericField($this->name, $title);
	}
	
	/**
	 * Return an encoding of the given value suitable for inclusion in a SQL statement.
	 *
	 * If necessary, this should include quotes.
	 *
	 * @param mixed
	 *
	 * @return string
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
