<?php
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
		
		$day = $month = $year = null;
		
		if( preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $val ) ) {
			$dateArray = explode( '/', $val );
			$val = $dateArray[2] . '-' . $dateArray[1] . '-' . $dateArray[0];
		}
		
		if($val) {
			$dateArray = explode( '-', $val );
			$day = $dateArray[2];
			$month = $dateArray[1];
			$year = $dateArray[0];
		} 
		
		if(preg_match('/(.*)[(.+)]$/', $this->name, $fieldNameParts)) {
			$fieldNamePrefix = $fieldNameParts[1];
			$fieldName = $fieldNameParts[2];
		} else {
			$fieldNamePrefix = $this->name;
			$fieldName = $this->name;
		}
		
		return <<<HTML
			<div class="dmycalendardate">
				<input type="hidden" id="$id" name="{$this->name}" value="$val" />
				<input type="text" id="$id-day" class="day numeric" name="{$fieldNamePrefix}[Day]" value="$day" maxlength="2" />/
				<input type="text" id="$id-month" class="month numeric" name="{$fieldNamePrefix}[Month]" value="$month" maxlength="2" />/
				<input type="text" id="$id-year" class="year numeric" name="{$fieldNamePrefix}[Year]" value="$year" maxlength="4" />
				<div class="calendarpopup" id="{$id}-calendar"></div>
			</div>
HTML;
	}
}
?>