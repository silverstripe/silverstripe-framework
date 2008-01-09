<?php

/**
 * @package sapphire
 * @subpackage model
 */

if(!class_exists('Datetime')) {
	/**
	 * @package sapphire
	 * @subpackage model
	 * @deprecated Use {@link SSDatetime} instead, as PHP 5.2 has its own Datetime class.  Object::create('Datetime') will instantiate an SSDatetime object.
	 */
	class Datetime extends Date {
		function __construct($name) {
			user_error('Datetime is deprecated. Use SSDatetime instead.', E_USER_NOTICE);
			parent::__construct($name);
		}
		
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
	
		function URLDatetime() {
			return date('Y-m-d%20H:i:s', strtotime($this->value));
		}
		
		function requireField() {
			DB::requireField($this->tableName, $this->name, "datetime");
		}
	}
}
?>
