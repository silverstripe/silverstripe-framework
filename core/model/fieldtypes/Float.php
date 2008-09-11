<?php
/**
 * Represents a floating point field.
 * @package sapphire
 * @subpackage model
 */
class Float extends DBField {
	
	function requireField() {
		DB::requireField($this->tableName, $this->name, "float");
	}
	
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