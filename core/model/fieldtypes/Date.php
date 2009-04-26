<?php
/**
 * Represents a date field.
 * @package sapphire
 * @subpackage model
 */
class Date extends DBField {
	
	function setValue($value) {
		if( is_array( $value ) && $value['Day'] && $value['Month'] && $value['Year'] ) {
			$this->value = $value['Year'] . '-' . $value['Month'] . '-' . $value['Day'];
			return;
		}
 		
		// Default to NZ date format - strtotime expects a US date
		if(ereg('^([0-9]+)/([0-9]+)/([0-9]+)$', $value, $parts)) 
			$value = "$parts[2]/$parts[1]/$parts[3]";

		if($value && is_string($value)) $this->value = date('Y-m-d', strtotime($value));
		else $value = null;
	}

	/**
	 * Returns the date in the format dd/mm/yy 
	 */	 
	function Nice() {
		if($this->value) return date('d/m/Y', strtotime($this->value));
	}
	
	/** 
	 * Returns the year from the given date
	 */
	function Year() {
		if($this->value) return date('Y', strtotime($this->value));
	}
	
	/**
	 * Returns the Full day, of the given date.
	 */
	function Day(){
		if($this->value) return date('l', strtotime($this->value));
	}
	
	/**
	 * Returns the month
	 */
	function ShortMonth() {
		if($this->value) return date('M', strtotime($this->value));
	}

	/**
	 * Returns the date of the month
	 */
	function DayOfMonth() {
		if($this->value) return date('j', strtotime($this->value));
	}
	
	
	/**
	 * Returns the date in the format 24 May 2006
	 */
	function Long() {
		if($this->value) return date('j F Y', strtotime($this->value));
	}
	
	/**
	 * Return the date formatted using the given PHP formatting string
	 */
	function Format($formattingString) {
		if($this->value) return date($formattingString, strtotime($this->value));
	}
	
	/**
	 * Return the date formatted using the given strftime formatting string.
	 *
	 * strftime obeys the current LC_TIME/LC_ALL when printing lexical values
	 * like day- and month-names
	 */
	function FormatI18N($formattingString) {
		if($this->value) return strftime($formattingString, strtotime($this->value));
	}
	
	/*
	 * Return a string in the form "12 - 16 Sept" or "12 Aug - 16 Sept"
	 */
	function RangeString($otherDateObj) {
		$d1 = $this->DayOfMonth();
		$d2 = $otherDateObj->DayOfMonth();
		$m1 = $this->ShortMonth();
		$m2 = $otherDateObj->ShortMonth();
		$y1 = $this->Year();
		$y2 = $otherDateObj->Year();
		
		if($y1 != $y2) return "$d1 $m1 $y1 - $d2 $m2 $y2";
		else if($m1 != $m2) return "$d1 $m1 - $d2 $m2 $y1";
		else return "$d1 - $d2 $m1 $y1";
	}
	
	function Rfc822() {
		if($this->value) return date('r', strtotime($this->value));
	}
	
	/**
	 * Returns the number of seconds/minutes/hours/days or months since the timestamp
	 */
	function Ago() {
		if($this->value) {
			if(time() > strtotime($this->value)) {
				return sprintf(
					_t(
						'Date.TIMEDIFFAGO',
						"%s ago",
						PR_MEDIUM,
						'Natural language time difference, e.g. 2 hours ago'
					),
					$this->TimeDiff()
				);
			} else {
				return sprintf(
					_t(
						'Date.TIMEDIFFAWAY',
						"%s away",
						PR_MEDIUM,
						'Natural language time difference, e.g. 2 hours away'
					),
					$this->TimeDiff()
				);
			}
		}
	}

	function TimeDiff() {

		if($this->value) {
			$ago = abs(time() - strtotime($this->value));
			
			if($ago < 60) {
				$span = $ago;
				return ($span != 1) ? "{$span} "._t("Date.SECS", " secs") : "{$span} "._t("Date.SEC", " sec");
			}
			if($ago < 3600) {
				$span = round($ago/60);
				return ($span != 1) ? "{$span} "._t("Date.MINS", " mins") : "{$span} "._t("Date.MIN", " min");
			}
			if($ago < 86400) {
				$span = round($ago/3600);
				return ($span != 1) ? "{$span} "._t("Date.HOURS", " hours") : "{$span} "._t("Date.HOUR", " hour");
			}
			if($ago < 86400*30) {
				$span = round($ago/86400);
				return ($span != 1) ? "{$span} "._t("Date.DAYS", " days") : "{$span} "._t("Date.DAY", " day");
			}
			if($ago < 86400*365) {
				$span = round($ago/86400/30);
				return ($span != 1) ? "{$span} "._t("Date.MONTHS", " months") : "{$span} "._t("Date.MONTH", " month");
			}
			if($ago > 86400*365) {
				$span = round($ago/86400/365);
				return ($span != 1) ? "{$span} "._t("Date.YEARS", " years") : "{$span} "._t("Date.YEAR", " year");
			}
		}
	}
	
	/**
	 * Gets the time difference, but always returns it in a certain format
	 * @param string $format The format, could be one of these: 
	 * 'seconds', 'minutes', 'hours', 'days', 'months', 'years'.
	 * 
	 * @return string
	 */
	function TimeDiffIn($format) {
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

	function requireField() {
		DB::requireField($this->tableName, $this->name, "date");
	}
	
	function InPast() {
		return strtotime( $this->value ) < time();
	}

	/**
	 * Returns a date suitable for insertion into a URL and use by the system.
	 */
	function URLDate() {
		return date('Y-m-d', strtotime($this->value));
	}
	
	
	function days_between($fyear, $fmonth, $fday, $tyear, $tmonth, $tday){
	  return abs((mktime ( 0, 0, 0, $fmonth, $fday, $fyear) - mktime ( 0, 0, 0, $tmonth, $tday, $tyear))/(60*60*24));
	}
	
	function day_before($fyear, $fmonth, $fday){
	  return date ("Y-m-d", mktime (0,0,0,$fmonth,$fday-1,$fyear));
	}
	
	function next_day($fyear, $fmonth, $fday){
	  return date ("Y-m-d", mktime (0,0,0,$fmonth,$fday+1,$fyear));
	}
	
	function weekday($fyear, $fmonth, $fday){ // 0 is a Monday
	  return (((mktime ( 0, 0, 0, $fmonth, $fday, $fyear) - mktime ( 0, 0, 0, 7, 17, 2006))/(60*60*24))+700000) % 7;
	}
	
	function prior_monday($fyear, $fmonth, $fday){
	  return date ("Y-m-d", mktime (0,0,0,$fmonth,$fday-weekday($fyear, $fmonth, $fday),$fyear)); 
	}
	
	/**
	 * Return the nearest date in the past, based on day and month. Automatically attaches the correct year.
	 * Useful for determining a financial year start date.
	 * 
	 * @param $fmonth int
	 * @param $fday int
	 * @param $fyear int Determine historical value
	 */
	static function past_date($fmonth, $fday = 1, $fyear = null) {
		if(!$fyear) $fyear = date('Y');
		$fday = (int)$fday;
		$fmonth = (int)$fmonth;
		$fyear = (int)$fyear;
		
		$pastDate = mktime(0,0,0, $fmonth,$fday,$fyear);
		$curDate = mktime(0,0,0,date('m'), date('d'),$fyear);

		// if the pastdate is actually past, select it with the current year
		// otherwise substract a year
		if($pastDate < $curDate) {
			return date("Y-m-d", mktime(0,0,0,$fmonth,$fday,$fyear));
		} else {
			return date("Y-m-d", mktime(0,0,0,$fmonth,$fday,$fyear-1));
		}
	}
	
	public function scaffoldFormField($title = null, $params = null) {
		return new DateField($this->name, $title);
	}
}

?>
