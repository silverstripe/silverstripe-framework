<?php
/**
 * A simple extension to dropdown field, pre-configured to list countries.
 * It will default to the country of the current visiotr.
 * @package forms
 * @subpackage fields-relational
 */
class CountryDropdownField extends DropdownField {
	function __construct($name, $title, $source = null, $value = "", $form=null, $emptyString="--select--") {
		if(!is_array($source)) {
			$source = Geoip::getCountryDropDown();
		}
		parent::__construct($name, $title, $source, $value, $form, $emptyString);
	}
	
	function Field() {
		if(!$this->value || !$this->source[$this->value]) $this->value = Geoip::visitor_country();
		return parent::Field();
	}
}

?>