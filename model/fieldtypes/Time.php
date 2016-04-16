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
	
	/**
	 * @config
	 * @see also Date::niceFormat
	 * @see also SS_DateTime::niceFormat
	 */
	private static $niceFormat = 'g:ia';
	
	
	public function setValue($value, $record = null) {
		if($value) {
			if(preg_match( '/(\d{1,2})[:.](\d{2})([a|A|p|P|][m|M])/', $value, $match )) $this->TwelveHour( $match );
			else $this->value = date('H:i:s', strtotime($value));
		} else {
			$value = null;
		}
	}

	/**
	 * Returns the time in the format specified by the config value niceFormat, or 12 hour format by default 
	 * e.g. "3:15pm"
	 * 
	 * @return string
	 */
	public function Nice() {
		if($this->value) return $this->Format(Config::inst()->get(__CLASS__, 'niceFormat'));
		
	}

	/**
	 * Return a user friendly format for time
	 * in a 24 hour format.
	 * 
	 * @return string Time in 24 hour format
	 * @deprecated 3.2 Use Nice() with config setting niceFormat instead
	 */
	public function Nice24() {
		Deprecation::notice('3.2', 'Use Nice() with config setting '.__CLASS__.'::niceFormat instead');
		if($this->value) return date('H:i', strtotime($this->value));
	}

	/**
	 * Return the time using a particular formatting string.
	 *
	 * @param string $format Format code string. e.g. "g:ia"
	 * @return string The date in the requested format
	 */
	public function Format($format) {
		if($this->value) return date($format, strtotime($this->value));
	}

	public function TwelveHour( $parts ) {
		$hour = $parts[1];
		$min = $parts[2];
		$half = $parts[3];

		// the transmation should exclude 12:00pm ~ 12:59pm
		$this->value = (( (strtolower($half) == 'pm') && $hour != '12') ? $hour + 12 : $hour ) .":$min:00";
	}

	public function requireField() {
		$parts=Array('datatype'=>'time', 'arrayValue'=>$this->arrayValue);
		$values=Array('type'=>'time', 'parts'=>$parts);
		DB::require_field($this->tableName, $this->name, $values);
	}

	public function scaffoldFormField($title = null, $params = null) {
		$field = TimeField::create($this->name, $title);

		// Show formatting hints for better usability
		$field->setDescription(sprintf(
			_t('FormField.Example', 'e.g. %s', 'Example format'),
			Convert::raw2xml(Zend_Date::now()->toString($field->getConfig('timeformat')))
		));
		$field->setAttribute('placeholder', $field->getConfig('timeformat'));

		return $field;
	}

}
