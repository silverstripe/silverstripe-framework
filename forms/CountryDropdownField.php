<?php

/**
 * A simple extension to dropdown field, pre-configured to list countries.
 * It will default to the country of the current visiotr.
 */
class CountryDropdownField extends DropdownField {
	function __construct($name, $title) {
		parent::__construct($name, $title, Geoip::getCountryDropDown());
	}
	
	function Field() {
		if(!$this->value || !$this->source[$this->value]) $this->value = Geoip::visitor_country();
		return parent::Field();
	}
}

?>