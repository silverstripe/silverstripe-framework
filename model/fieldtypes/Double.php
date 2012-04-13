<?php
/**
 * 
 * @package framework
 * @subpackage model
 */
class Double extends DBField {
	
	function requireField() {
	
		// HACK: MSSQL does not support double so we're usinf float instead
		// @todo This should go into MSSQLDatabase ideally somehow
		if(DB::getConn() instanceof MySQLDatabase) {
			DB::requireField($this->tableName, $this->name, "double");
		} else {
			DB::requireField($this->tableName, $this->name, "float");
		}
	}
	
	function Nice() {
		return number_format($this->value, 2);
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
