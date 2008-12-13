<?php
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
		Requirements::javascript(THIRDPARTY_DIR . "/behaviour.js");
		Requirements::javascript(THIRDPARTY_DIR . "/calendar/calendar.js");
		Requirements::javascript(THIRDPARTY_DIR . "/calendar/lang/calendar-en.js");
		Requirements::javascript(THIRDPARTY_DIR . "/calendar/calendar-setup.js");
		Requirements::css(SAPPHIRE_DIR . "/css/CalendarDateField.css");
		Requirements::css(THIRDPARTY_DIR . "/calendar/calendar-win2k-1.css");

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