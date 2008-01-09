<?php

/**
 * @package forms
 * @subpackage fields-datetime
 */

/**
 * Displays a date field with day, month and year boxes, with a calendar to select
 * the date
 * @package forms
 * @subpackage fields-datetime
 */
class DMYCalendarDateField extends CalendarDateField {
	
	function Field() {
		Requirements::javascript("jsparty/calendar/calendar.js");
		Requirements::javascript("jsparty/calendar/lang/calendar-en.js");
		Requirements::javascript("jsparty/calendar/calendar-setup.js");
		Requirements::css("sapphire/css/CalendarDateField.css");
		Requirements::css("jsparty/calendar/calendar-win2k-1.css");
		Requirements::javascript("sapphire/javascript/CalendarDateField.js");

		$field = DateField::Field();

		$id = $this->id();
		$val = $this->attrValue();
		
		if( preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $val ) ) {
			$dateArray = explode( '/', $val );
			
			$val = $dateArray[2] . '-' . $dateArray[1] . '-' . $dateArray[0];
		}
		
		$dateArray = explode( '-', $val );
		
		$day = $dateArray[2];
		$month = $dateArray[1];
		$year = $dateArray[0];
		
		preg_match('/(.*)[(.+)]$/', $this->name, $fieldNameParts );
		
		$fieldNamePrefix = $fieldNameParts[1];
		$fieldName = $fieldNameParts[2];
		
		return <<<HTML
			<div class="dmycalendardate">
				<input type="hidden" id="$id" name="{$this->name}" value="$val" />
				<input type="text" id="$id-day" class="day numeric" name="{$fieldNamePrefix}[{$fieldName}-Day]" value="$day" maxlength="2" />/
				<input type="text" id="$id-month" class="month numeric" name="{$fieldNamePrefix}[{$fieldName}-Month]" value="$month" maxlength="2" />/
				<input type="text" id="$id-year" class="year numeric" name="{$fieldNamePrefix}[{$fieldName}-Year]" value="$year" maxlength="4" />
				<div class="calendarpopup" id="{$id}-calendar"></div>
			</div>
HTML;
	}
}
?>