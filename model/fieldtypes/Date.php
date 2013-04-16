<?php
/**
 * Represents a date field.
 * The field currently supports New Zealand date format (DD/MM/YYYY),
 * or an ISO 8601 formatted date (YYYY-MM-DD).
 * Alternatively you can set a timestamp that is evaluated through
 * PHP's built-in date() function according to your system locale.
 * 
 * Example definition via {@link DataObject::$db}:
 * <code>
 * static $db = array(
 * 	"Expires" => "Date",
 * );
 * </code>
 * 
 * @todo Add localization support, see http://open.silverstripe.com/ticket/2931
 * 
 * @package framework
 * @subpackage model
 */
class Date extends DBField {
	
	public function setValue($value, $record = null) {
		if($value === false || $value === null || (is_string($value) && !strlen($value))) {
			// don't try to evaluate empty values with strtotime() below, as it returns "1970-01-01" when it should be
			// saved as NULL in database
			$this->value = null;
			return;
		}

		// @todo This needs tidy up (what if you only specify a month and a year, for example?)
		if(is_array($value)) {
			if(!empty($value['Day']) && !empty($value['Month']) && !empty($value['Year'])) {
				$this->value = $value['Year'] . '-' . $value['Month'] . '-' . $value['Day'];
				return;
			} else {
				// return nothing (so checks below don't fail on an empty array)
				return null;
			}
		}
		
		// Default to NZ date format - strtotime expects a US date
		if(preg_match('#^([0-9]+)/([0-9]+)/([0-9]+)$#', $value, $parts)) {
			$value = "$parts[2]/$parts[1]/$parts[3]";			
		}

		if(is_numeric($value)) {
			$this->value = date('Y-m-d', $value);
		} elseif(is_string($value)) {
			try{
				$date = new DateTime($value);
				$this->value = $date->Format('Y-m-d');
				return;
			}catch(Exception $e){
				$this->value = null;
				return;
			}
		}
	}

	/**
	 * Returns the date in the format dd/mm/yy 
	 */	 
	public function Nice() {
		if($this->value) return $this->Format('d/m/Y');
	}
	
	/**
	 * Returns the date in US format: “01/18/2006”
	 */
	public function NiceUS() {
		if($this->value) return $this->Format('m/d/Y');
	}
	
	/** 
	 * Returns the year from the given date
	 */
	public function Year() {
		if($this->value) return $this->Format('Y');
	}
	
	/**
	 * Returns the Full day, of the given date.
	 */
	public function Day(){
		if($this->value) return $this->Format('l');
	}
	
	/**
	 * Returns a full textual representation of a month, such as January.
	 */
	public function Month() {
		if($this->value) return $this->Format('F');
	}
	
	/**
	 * Returns the short version of the month such as Jan
	 */
	public function ShortMonth() {
		if($this->value) return $this->Format('M');
	}

	/**
	 * Returns the day of the month.
	 * @param boolean $includeOrdinals Include ordinal suffix to day, e.g. "th" or "rd"
	 * @return string
	 */
	public function DayOfMonth($includeOrdinal = false) {
		if($this->value) {
			$format = 'j';
			if ($includeOrdinal) $format .= 'S';
			return $this->Format($format);
		}
	}
	
	/**
	 * Returns the date in the format 24 December 2006
	 */
	public function Long() {
		if($this->value) return $this->Format('j F Y');
	}
	
	/**
	 * Returns the date in the format 24 Dec 2006
	 */
	public function Full() {
		if($this->value) return $this->Format('j M Y');
	}
	
	/**
	 * Return the date using a particular formatting string.
	 * 
	 * @param string $format Format code string. e.g. "d M Y" (see http://php.net/date)
	 * @return string The date in the requested format
	 */
	public function Format($format) {
		if($this->value){
			$date = new DateTime($this->value);
			return $date->Format($format);
		}
	}
	
	/**
	 * Return the date formatted using the given strftime formatting string.
	 *
	 * strftime obeys the current LC_TIME/LC_ALL when printing lexical values
	 * like day- and month-names
	 */
	public function FormatI18N($formattingString) {
		if($this->value) {
			return strftime($formattingString, strtotime($this->value));
		}
	}
	
	/*
	 * Return a string in the form "12 - 16 Sept" or "12 Aug - 16 Sept"
	 * @param Date $otherDateObj Another date object specifying the end of the range
	 * @param boolean $includeOrdinals Include ordinal suffix to day, e.g. "th" or "rd"
	 * @return string
	 */
	public function RangeString($otherDateObj, $includeOrdinals = false) {
		$d1 = $this->DayOfMonth($includeOrdinals);
		$d2 = $otherDateObj->DayOfMonth($includeOrdinals);
		$m1 = $this->ShortMonth();
		$m2 = $otherDateObj->ShortMonth();
		$y1 = $this->Year();
		$y2 = $otherDateObj->Year();
		
		if($y1 != $y2) return "$d1 $m1 $y1 - $d2 $m2 $y2";
		else if($m1 != $m2) return "$d1 $m1 - $d2 $m2 $y1";
		else return "$d1 - $d2 $m1 $y1";
	}
	
	public function Rfc822() {
		if($this->value) return date('r', strtotime($this->value));
	}
	
	public function Rfc2822() {
		if($this->value) return date('Y-m-d H:i:s', strtotime($this->value));
	}
	
	public function Rfc3339() {
		$timestamp = ($this->value) ? strtotime($this->value) : false;
		if(!$timestamp) return false;
		
		$date = date('Y-m-d\TH:i:s', $timestamp);
		
		$matches = array();
		if(preg_match('/^([\-+])(\d{2})(\d{2})$/', date('O', $timestamp), $matches)) {
			$date .= $matches[1].$matches[2].':'.$matches[3];
		} else {
			$date .= 'Z';
		}
		
		return $date;
	}
	
	/**
	 * Returns the number of seconds/minutes/hours/days or months since the timestamp.
	 *
	 * @param boolean $includeSeconds Show seconds, or just round to "less than a minute".
	 * @return  String
	 */
	public function Ago($includeSeconds = true) {
		if($this->value) {
			$time = SS_Datetime::now()->Format('U');
			if(strtotime($this->value) == $time || $time > strtotime($this->value)) {
				return _t(
					'Date.TIMEDIFFAGO',
					"{difference} ago",
					'Natural language time difference, e.g. 2 hours ago',
					array('difference' => $this->TimeDiff($includeSeconds))
				);
			} else {
				return _t(
					'Date.TIMEDIFFIN',
					"in {difference}",
					'Natural language time difference, e.g. in 2 hours',
					array('difference' => $this->TimeDiff($includeSeconds))
				);
			}
		}
	}

	/**
	 * @param boolean $includeSeconds Show seconds, or just round to "less than a minute".
	 * @return  String
	 */
	public function TimeDiff($includeSeconds = true) {
		if(!$this->value) return false;

		$time = SS_Datetime::now()->Format('U');
		$ago = abs($time - strtotime($this->value));
		
		if($ago < 60 && $includeSeconds) {
			$span = $ago;
			$result = ($span != 1) ? "{$span} "._t("Date.SECS", "secs") : "{$span} "._t("Date.SEC", "sec");
		} elseif($ago < 60) {
			$result = _t('Date.LessThanMinuteAgo', 'less than a minute');
		} elseif($ago < 3600) {
			$span = round($ago/60);
			$result = ($span != 1) ? "{$span} "._t("Date.MINS", "mins") : "{$span} "._t("Date.MIN", "min");
		} elseif($ago < 86400) {
			$span = round($ago/3600);
			$result = ($span != 1) ? "{$span} "._t("Date.HOURS", "hours") : "{$span} "._t("Date.HOUR", "hour");
		} elseif($ago < 86400*30) {
			$span = round($ago/86400);
			$result = ($span != 1) ? "{$span} "._t("Date.DAYS", "days") : "{$span} "._t("Date.DAY", "day");
		} elseif($ago < 86400*365) {
			$span = round($ago/86400/30);
			$result = ($span != 1) ? "{$span} "._t("Date.MONTHS", "months") : "{$span} "._t("Date.MONTH", "month");
		} elseif($ago > 86400*365) {
			$span = round($ago/86400/365);
			$result = ($span != 1) ? "{$span} "._t("Date.YEARS", "years") : "{$span} "._t("Date.YEAR", "year");
		}
		
		// Replace duplicate spaces, backwards compat with existing translations
		$result = preg_replace('/\s+/', ' ', $result);

		return $result;
	}
	
	/**
	 * Gets the time difference, but always returns it in a certain format
	 * @param string $format The format, could be one of these: 
	 * 'seconds', 'minutes', 'hours', 'days', 'months', 'years'.
	 * 
	 * @return string
	 */
	public function TimeDiffIn($format) {
		if($this->value) {
			$ago = abs(time() - strtotime($this->value));
			
			switch($format) {
				case "seconds":
					$span = $ago;
					return ($span != 1) ? "{$span} seconds" : "{$span} second";
				break;
				case "minutes":
					$span = round($ago/60);
					return ($span != 1) ? "{$span} minutes" : "{$span} minute";
				break;
				case "hours":
					$span = round($ago/3600);
					return ($span != 1) ? "{$span} hours" : "{$span} hour";
				break;
				case "days":
					$span = round($ago/86400);
					return ($span != 1) ? "{$span} days" : "{$span} day";
				break;
				case "months":
					$span = round($ago/86400/30);
					return ($span != 1) ? "{$span} months" : "{$span} month";
				break;
				case "years":
					$span = round($ago/86400/365);
					return ($span != 1) ? "{$span} years" : "{$span} year";
				break;
			}
		}
	}

	public function requireField() {
		$parts=Array('datatype'=>'date', 'arrayValue'=>$this->arrayValue);
		$values=Array('type'=>'date', 'parts'=>$parts);
		DB::requireField($this->tableName, $this->name, $values);
	}
	
	/**
	 * Returns true if date is in the past.
	 * @return boolean
	 */
	public function InPast() {
		return strtotime($this->value) < SS_Datetime::now()->Format('U');
	}
	
	/**
	 * Returns true if date is in the future.
	 * @return boolean
	 */
	public function InFuture() {
		return strtotime($this->value) > SS_Datetime::now()->Format('U');
	}
	
	/**
	 * Returns true if date is today.
	 * @return boolean
	 */
	public function IsToday() {
		return (date('Y-m-d', strtotime($this->value)) == SS_Datetime::now()->Format('Y-m-d'));
	}

	/**
	 * Returns a date suitable for insertion into a URL and use by the system.
	 */
	public function URLDate() {
		return date('Y-m-d', strtotime($this->value));
	}
	
	
	public function days_between($fyear, $fmonth, $fday, $tyear, $tmonth, $tday){
		return abs((mktime ( 0, 0, 0, $fmonth, $fday, $fyear) - mktime ( 0, 0, 0, $tmonth, $tday, $tyear))/(60*60*24));
	}
	
	public function day_before($fyear, $fmonth, $fday){
		return date ("Y-m-d", mktime (0,0,0,$fmonth,$fday-1,$fyear));
	}
	
	public function next_day($fyear, $fmonth, $fday){
		return date ("Y-m-d", mktime (0,0,0,$fmonth,$fday+1,$fyear));
	}
	
	public function weekday($fyear, $fmonth, $fday){ // 0 is a Monday
		return (((mktime ( 0, 0, 0, $fmonth, $fday, $fyear) - mktime ( 0, 0, 0, 7, 17, 2006))/(60*60*24))+700000) % 7;
	}
	
	public function prior_monday($fyear, $fmonth, $fday){
		return date ("Y-m-d", mktime (0,0,0,$fmonth,$fday-$this->weekday($fyear, $fmonth, $fday),$fyear)); 
	}
	
	/**
	 * Return the nearest date in the past, based on day and month.
	 * Automatically attaches the correct year.
	 * 
	 * This is useful for determining a financial year start or end date.
	 * 
	 * @param $fmonth int The number of the month (e.g. 3 is March, 4 is April)
	 * @param $fday int The day of the month
	 * @param $fyear int Determine historical value
	 * @return string Date in YYYY-MM-DD format
	 */
	public static function past_date($fmonth, $fday = 1, $fyear = null) {
		if(!$fyear) $fyear = date('Y');
		$fday = (int) $fday;
		$fmonth = (int) $fmonth;
		$fyear = (int) $fyear;
		
		$pastDate = mktime(0, 0, 0, $fmonth, $fday, $fyear);
		$curDate = mktime(0, 0, 0, date('m'), date('d'), $fyear);

		if($pastDate < $curDate) {
			return date('Y-m-d', mktime(0, 0, 0, $fmonth, $fday, $fyear));
		} else {
			return date('Y-m-d', mktime(0, 0, 0, $fmonth, $fday, $fyear - 1));
		}
	}
	
	public function scaffoldFormField($title = null, $params = null) {
		$field = DateField::create($this->name, $title);
		
		// Show formatting hints for better usability
		$field->setDescription(sprintf(
			_t('FormField.Example', 'e.g. %s', 'Example format'),
			Convert::raw2xml(Zend_Date::now()->toString($field->getConfig('dateformat')))
		));
		$field->setAttribute('placeholder', $field->getConfig('dateformat'));
		
		return $field;
	}
}
