<?php
/**
 * 
 * @package sapphire
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
}
?>