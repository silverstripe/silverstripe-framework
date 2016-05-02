<?php
/**
 * Represents a Decimal field.
 *
 * @package framework
 * @subpackage model
 */
class Decimal extends DBField {

	protected $wholeSize, $decimalSize, $defaultValue;

	/**
	 * Create a new Decimal field.
	 *
	 * @param string $name
	 * @param int $wholeSize
	 * @param int $decimalSize
	 * @param float $defaultValue
	 */
	public function __construct($name = null, $wholeSize = 9, $decimalSize = 2, $defaultValue = 0) {
		$this->wholeSize = is_int($wholeSize) ? $wholeSize : 9;
		$this->decimalSize = is_int($decimalSize) ? $decimalSize : 2;

		$this->defaultValue = number_format((float) $defaultValue, $decimalSize);;

		parent::__construct($name);
	}

	/**
	 * @return float
	 */
	public function Nice() {
		return number_format($this->value, $this->decimalSize);
	}

	/**
	 * @return int
	 */
	public function Int() {
		return floor($this->value);
	}

	public function requireField() {
		$parts = array(
			'datatype' => 'decimal',
			'precision' => "$this->wholeSize,$this->decimalSize",
			'default' => $this->defaultValue,
			'arrayValue' => $this->arrayValue
		);

		$values = array(
			'type' => 'decimal',
			'parts' => $parts
		);

		DB::require_field($this->tableName, $this->name, $values);
	}

	/**
	 * @param DataObject $dataObject
	 */
	public function saveInto($dataObject) {
		$fieldName = $this->name;

		if($fieldName) {
			$dataObject->$fieldName = (float)preg_replace('/[^0-9.\-\+]/', '', $this->value);
		} else {
			user_error("DBField::saveInto() Called on a nameless '" . get_class($this) . "' object", E_USER_ERROR);
		}
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
	 * @return float
	 */
	public function nullValue() {
		return 0;
	}

	public function prepValueForDB($value) {
		if($value === true) {
			return 1;
		} elseif(empty($value) || !is_numeric($value)) {
			return 0;
		}

		return $value;
	}
}
