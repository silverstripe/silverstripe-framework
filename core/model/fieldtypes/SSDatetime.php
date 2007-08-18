<?php
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
	
	function __construct( $name ) {
		// Debug::show( 'Created SSDatetime: ' . $name );
		parent::__construct( $name );
	}
}

?>
