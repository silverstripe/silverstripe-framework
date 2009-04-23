<?php
/**
 * 
 * @package sapphire
 * @subpackage model
 */
class Double extends DBField {
	
	function requireField() {
		DB::requireField($this->tableName, $this->name, "double");
	}
	
	function Nice() {
		return number_format($this->value, 2);
	}	
}
?>