<?php
/**
 * Represents a date-time field.
 * The field currently supports New Zealand date format (DD/MM/YYYY),
 * or an ISO 8601 formatted date and time (Y-m-d H:i:s).
 * Alternatively you can set a timestamp that is evaluated through
 * PHP's built-in date() and strtotime() function according to your system locale.
 * 
 * For all computations involving the current date and time,
 * please use {@link SS_Datetime::now()} instead of PHP's built-in date() and time()
 * methods. This ensures that all time-based computations are testable with mock dates
 * through {@link SS_Datetime::set_mock_now()}.
 * 
 * Example definition via {@link DataObject::$db}:
 * <code>
 * static $db = array(
 *  "Expires" => "SS_Datetime",
 * );
 * </code>
 * 
 * @todo Add localization support, see http://open.silverstripe.com/ticket/2931
 * 
 * @package framework
 * @subpackage model
 */
class SS_Datetime extends Date implements TemplateGlobalProvider {
	
	function setValue($value, $record = null) {
		if($value === false || $value === null || (is_string($value) && !strlen($value))) {
			// don't try to evaluate empty values with strtotime() below, as it returns "1970-01-01" when it should be saved as NULL in database
			$this->value = null;
			return;
		}

		// Default to NZ date format - strtotime expects a US date
		if(preg_match('#^([0-9]+)/([0-9]+)/([0-9]+)$#', $value, $parts)) {
			$value = "$parts[2]/$parts[1]/$parts[3]";
		}

		if(is_numeric($value)) {
			$this->value = date('Y-m-d H:i:s', $value);
		} elseif(is_string($value)) {
			// $this->value = date('Y-m-d H:i:s', strtotime($value));
			try{
				$date = new DateTime($value);
				$this->value = $date->Format('Y-m-d H:i:s');
				return;
			}catch(Exception $e){
				$this->value = null;
				return;
			}
		}
	}

	/**
	 * Returns the date in the raw SQL-format, e.g. “2006-01-18 16:32:04”
	 */
	function Nice() {
		if($this->value) return $this->Format('d/m/Y g:ia');
	}

	function Nice24() {
		if($this->value) return $this->Format('d/m/Y H:i');
	}

	function Date() {
		if($this->value) return $this->Format('d/m/Y');
	}

	function Time() {
		if($this->value) return $this->Format('g:ia');
	}

	function Time24() {
		if($this->value) return $this->Format('H:i');
	}

	function requireField() {
		$parts=Array('datatype'=>'datetime', 'arrayValue'=>$this->arrayValue);
		$values=Array('type'=>'SS_Datetime', 'parts'=>$parts);
		DB::requireField($this->tableName, $this->name, $values);
	}
	
	function URLDatetime() {
		if($this->value) return $this->Format('Y-m-d%20H:i:s');
	}
	
	public function scaffoldFormField($title = null, $params = null) {
		return new DatetimeField($this->name, $title);
	}
	
	/**
	 * 
	 */
	protected static $mock_now = null;
	
	/**
	 * Returns either the current system date as determined
	 * by date(), or a mocked date through {@link set_mock_now()}.
	 * 
	 * @return SS_Datetime
	 */
	static function now() {
		if(self::$mock_now) {
			return self::$mock_now;
		} else {
			return DBField::create_field('SS_Datetime', date('Y-m-d H:i:s'));
		}
	}
	
	/**
	 * Mock the system date temporarily, which is useful for time-based unit testing.
	 * Use {@link clear_mock_now()} to revert to the current system date.
	 * Caution: This sets a fixed date that doesn't increment with time.
	 * 
	 * @param SS_Datetime|string $datetime Either in object format, or as a SS_Datetime compatible string.
	 */
	static function set_mock_now($datetime) {
		if($datetime instanceof SS_Datetime) {
			self::$mock_now = $datetime;
		} elseif(is_string($datetime)) {
			self::$mock_now = DBField::create_field('SS_Datetime', $datetime);
		} else {
			throw new Exception('SS_Datetime::set_mock_now(): Wrong format: ' . $datetime);
		}
	}
	
	/**
	 * Clear any mocked date, which causes
	 * {@link Now()} to return the current system date.
	 */
	static function clear_mock_now() {
		self::$mock_now = null;
	}
	
	public static function get_template_global_variables() {
		return array(
			'Now' => array('method' => 'now', 'casting' => 'SS_Datetime'),
		);
	}
}

