<?php

/**
 * A simple extension to {@link DropdownField}, pre-configured to list countries.
 * It will default to the country of the current visitor or use the default
 * country code provided using {@link Geoip::set_default_country_code()}.
 *
 * @package forms
 * @subpackage fields-relational
 */
class CountryDropdownField extends DropdownField {
	
	protected $defaultToVisitorCountry = true;
	
	function __construct($name, $title = null, $source = null, $value = "", $form=null) {
		if(!is_array($source)) $source = Geoip::getCountryDropDown();
		if(!$value) $value = Geoip::visitor_country();

		parent::__construct($name, ($title===null) ? $name : $title, $source, $value, $form);
	}
	
	function defaultToVisitorCountry($val) {
		$this->defaultToVisitorCountry = $val;
	}
	
	function Field() {
		$source = $this->getSource();
		
		if($this->defaultToVisitorCountry && !$this->value || !isset($source[$this->value])) {
			$this->value = ($vc = Geoip::visitor_country()) ? $vc : Geoip::get_default_country_code();
		}
		
		return parent::Field();
	}
}