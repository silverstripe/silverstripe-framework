<?php
/**
 * Represents a column in the database with the type 'Time'.
 * 
 * Example definition via {@link DataObject::$db}:
 * <code>
 * static $db = array(
 * 	"StartTime" => "Time",
 * );
 * </code>
 * 
 * @todo Add localization support, see http://open.silverstripe.com/ticket/2931
 * 
 * @package framework
 * @subpackage model
 */
class Time extends DBField {

	function setValue($value, $record = null) {
		if($value) {
			if(preg_match( '/(\d{1,2})[:.](\d{2})([a|A|p|P|][m|M])/', $value, $match )) $this->TwelveHour( $match );
			else $this->value = date('H:i:s', strtotime($value));
		} else { 
			$value = null;
		}
	}

	/**
	 * Return a user friendly format for time
	 * in a 12 hour format.
	 * 
	 * @return string Time in 12 hour format
	 */
	function Nice() {
		if($this->value) return date('g:ia', strtotime($this->value));
	}

	/**
	 * Return a user friendly format for time
	 * in a 24 hour format.
	 * 
	 * @return string Time in 24 hour format
	 */
	function Nice24() {
		if($this->value) return date('H:i', strtotime($this->value));
	}
	
	/**
	 * Return the time using a particular formatting string.
	 * 
	 * @param string $format Format code string. e.g. "g:ia"
	 * @return string The date in the requested format
	 */
	function Format($format) {
		if($this->value) return date($format, strtotime($this->value));
	}
	
	function TwelveHour( $parts ) {
		$hour = $parts[1];
		$min = $parts[2];
		$half = $parts[3];
		
		// the transmation should exclude 12:00pm ~ 12:59pm
		$this->value = (( (strtolower($half) == 'pm') && $hour != '12') ? $hour + 12 : $hour ) .":$min:00";
	}

	function requireField() {
		$parts=Array('datatype'=>'time', 'arrayValue'=>$this->arrayValue);
		$values=Array('type'=>'time', 'parts'=>$parts);
		DB::requireField($this->tableName, $this->name, $values);
	}
	
	public function scaffoldFormField($title = null, $params = null) {
		return new TimeField($this->name, $title);
	}
	
}
