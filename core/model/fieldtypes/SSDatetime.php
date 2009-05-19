<?php
/**
 * Represents a date-time field.
 * The field currently supports New Zealand date format (DD/MM/YYYY),
 * or an ISO 8601 formatted date and time (Y-m-d H:i:s).
 * Alternatively you can set a timestamp that is evaluated through
 * PHP's built-in date() and strtotime() function according to your system locale.
 * 
 * @todo Add localization support, see http://open.silverstripe.com/ticket/2931
 * 
 * @package sapphire
 * @subpackage model
 */
class SSDatetime extends Date {
	
	function setValue($value) {
		// Default to NZ date format - strtotime expects a US date
		if(ereg('^([0-9]+)/([0-9]+)/([0-9]+)$', $value, $parts)) {
			$value = "$parts[2]/$parts[1]/$parts[3]";
		}
		
		if(is_numeric($value)) {
			$this->value = date('Y-m-d H:i:s', $value);
		} elseif(is_string($value)) {
			$this->value = date('Y-m-d H:i:s', strtotime($value));
		}
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
		$parts=Array('datatype'=>'datetime');
		$values=Array('type'=>'ssdatetime', 'parts'=>$parts);
		DB::requireField($this->tableName, $this->name, $values);
	}
	
	function URLDatetime() {
		return date('Y-m-d%20H:i:s', strtotime($this->value));
	}
	
	public function scaffoldFormField($title = null, $params = null) {
		return new PopupDateTimeField($this->name, $title);
	}
}

?>