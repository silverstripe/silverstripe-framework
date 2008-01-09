<?php

/**
 * @package forms
 * @subpackage fields-datetime
 */

/**
 * This field creates a date field that shows a calendar on pop-up
 * @package forms
 * @subpackage fields-datetime
 */
class CalendarDateField extends DateField {
	protected $futureOnly;
	
	static function HTMLField( $id, $name, $val ) {
		return <<<HTML
			<input type="text" id="$id" name="$name" value="$val" />
			<img src="sapphire/images/calendar-icon.gif" id="$id-icon" />
			<div class="calendarpopup" id="$id-calendar"></div>
HTML;
	}
	
	function Field() {
		Requirements::javascript("jsparty/calendar/calendar.js");
		Requirements::javascript("jsparty/calendar/lang/calendar-en.js");
		Requirements::javascript("jsparty/calendar/calendar-setup.js");
		Requirements::css("sapphire/css/CalendarDateField.css");
		Requirements::css("jsparty/calendar/calendar-win2k-1.css");

		$field = parent::Field();

		$id = $this->id();
		$val = $this->attrValue();
		
		$futureClass = $this->futureOnly ? ' futureonly' : '';
		
		$innerHTML = self::HTMLField( $id, $this->name, $val );
		
		return <<<HTML
			<div class="calendardate$futureClass">
				$innerHTML
			</div>
HTML;
	}
	
	/**
	 * Sets the field so that only future dates can be set on them
	 */
	function futureDateOnly() {
		$this->futureOnly = true;
	}
}

?>