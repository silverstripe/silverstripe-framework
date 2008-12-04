<?php
/**
 * Represents a date-time field.
 * @package sapphire
 * @subpackage model
 */
class SSDatetime extends Date {
	function setValue($value) {
		if($value) $this->value = date('Y-m-d H:i:s', strtotime($value));
		else $value = null;
	}

	function Nice() {
		return date('d/m/Y g:ia', strtotime($this->value));
	}
	function Nice24() {
		return date('d/m/Y H:i', strtotime($this->value));
	}
	function Date() {
		return date('d/m/Y', strtotime($this->value));
	}
	function Time() {
		return date('g:ia', strtotime($this->value));
	}
	function Time24() {
		return date('H:i', strtotime($this->value));
	}

	function requireField() {
		DB::requireField($this->tableName, $this->name, "datetime");
	}
	
	function URLDatetime() {
		return date('Y-m-d%20H:i:s', strtotime($this->value));
	}
	
	public function scaffoldFormField($title = null, $params = null) {
		return new PopupDateTimeField($this->name, $title);
	}
}

?>