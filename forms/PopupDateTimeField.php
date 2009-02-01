<?php
/**
 * Field for entering a date/time pair.
 * @package forms
 * @subpackage fields-datetime
 */
class PopupDateTimeField extends CalendarDateField {
	
	/**
	 * @todo js validation needs to be implemented.
	 * @see sapphire/forms/DateField#jsValidation()
	 */
	function jsValidation() {
	}
	
	/**
	 * @todo php validation needs to be implemented.
	 * @see sapphire/forms/DateField#validate($validator)
	 */
	function validate() {
		return true;
	}
	
	function Field() {
		
		Requirements::css( SAPPHIRE_DIR . '/css/PopupDateTimeField.css' );
		
		$field = parent::Field();
		
		DropdownTimeField::Requirements();
		
		$id = $this->id();
		$val = $this->attrValue();
		
		$date = $this->attrValueDate();
		$time = $this->attrValueTime();
		
		$futureClass = $this->futureOnly ? ' futureonly' : '';
		
		$innerHTMLDate = parent::HTMLField( $id . '_Date', $this->name . '[Date]', $date );
		$innerHTMLTime = DropdownTimeField::HTMLField( $id . '_Time', $this->name . '[Time]', $time );
				
		return <<<HTML
			<div class="popupdatetime">
				<ul>
					<li class="calendardate$futureClass">$innerHTMLDate</li>
					<li class="dropdowntime">$innerHTMLTime</li>
				</ul>
			</div>
HTML;
	}
	
	function attrValueDate() {
		if( $this->value )
			return date( 'd/m/Y', strtotime( $this->value ) );	
		else
			return '';
	}
	
	function attrValueTime() {
		if( $this->value )
			return date( 'h:i a', strtotime( $this->value ) );	
		else
			return '';
	}
	
	function setValue( $val ) {
		if( is_array( $val ) ) {	
			
			// 1) Date
			
			if( $val[ 'Date' ] && preg_match( '/^([\d]{1,2})\/([\d]{1,2})\/([\d]{2,4})/', $val[ 'Date' ], $parts ) )
				$date = "$parts[3]-$parts[2]-$parts[1]";
			else
				$date = null;
			
			// 2) Time
			
			$time = $val[ 'Time' ] ? date( 'H:i:s', strtotime( $val[ 'Time' ] ) ) : null;
	
			if( $date == null )
				$this->value = $time;
			else if( $time == null )
				$this->value = $date;
			else
				$this->value = $date . ' ' . $time;
		}
		else
			$this->value = $val;
	}

	function dataValue() {
		return $this->value;
	}
}

?>