<?php
/**
 * A simple extension to dropdown field, pre-configured to list countries.
 * It will default to the country of the current visiotr.
 * @package forms
 * @subpackage fields-relational
 */
class CountryDropdownField extends DropdownField {
	protected $defaultToVisitorCountry = true;
	
	function __construct($name, $title, $source = null, $value = "", $form=null, $emptyString="--select--") {
		if(!is_array($source)) {
			$source = Geoip::getCountryDropDown();
		}
		parent::__construct($name, $title, $source, $value, $form, $emptyString);
	}
	
	function defaultToVisitorCountry($val) {
		$this->defaultToVisitorCountry = $val;
	}
	
	function Field() {
		if($this->defaultToVisitorCountry && !$this->value || !isset($this->source[$this->value])) $this->value = Geoip::visitor_country();
		return parent::Field();
	}
}

?>