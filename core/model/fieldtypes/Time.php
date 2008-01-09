<?php

/**
 * @package sapphire
 * @subpackage model
 */

/**
 * Represents a column in the database with the type 'Time'
 * @package sapphire
 * @subpackage model
 */
class Time extends DBField {

	function setVal($value) {
		
		if($value) {
			if(preg_match( '/(\d{1,2})[:.](\d{2})([ap]m)/', $value, $match )) $this->_12Hour( $match );
			else $this->value = date('H:i:s', strtotime($value));
		} else $value = null;
	}
	
	function setValue($value) {
		return $this->setVal( $value );
	}

	function Nice() {
		return date('g:ia', strtotime($this->value));
	}
	
	function Nice24() {
		return date('H:i', strtotime($this->value));
	}

	
	function _12Hour( $parts ) {
		$hour = $parts[1];
		$min = $parts[2];
		$half = $parts[3];
		
		$this->value = (( $half == 'pm' ) ? $hour + 12 : $hour ) .":$min:00";
	}

	function requireField() {
		DB::requireField($this->tableName, $this->name, "time");
	}
}
?>