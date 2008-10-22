<?php

/**
 * @package forms
 * @subpackage fields-relational
 */

/**
 * A simple extension to dropdown field, pre-configured to list countries.
 * It will default to the country of the current visiotr.
 * @package forms
 * @subpackage fields-relational
 */
class CountryDropdownField extends DropdownField {
	function __construct($name, $title, $value = '') {
		if(!$value) {
			$value = Geoip::visitor_country();
		}
		
		parent::__construct($name, $title, Geoip::getCountryDropDown(), $value);
	}
}

?>
