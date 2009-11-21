<?php
/**
 * This field creates a date field that shows a calendar on pop-up
 * @package forms
 * @subpackage fields-datetime
 */
class CalendarDateField extends DateField {
	
	protected $futureOnly;
		
	function Field() {
		// javascript: core
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery/jquery.js');
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/jquery_improvements.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-ui/ui.core.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-ui/ui.datepicker.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-metadata/jquery.metadata.js');
		
		// javascript: localized datepicker
		// Include 
		$candidates = array(
			i18n::convert_rfc1766(i18n::get_locale()), 
			i18n::get_lang_from_locale(i18n::get_locale())
		);
		foreach($candidates as $candidate) {
			$datePickerI18nPath = sprintf(SAPPHIRE_DIR . '/thirdparty/jquery-ui/i18n/ui.datepicker-%s.js', $candidate);
			if(Director::fileExists($datePickerI18nPath)) Requirements::javascript($datePickerI18nPath);
		}
		
		
		// javascript: concrete
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-selector/src/jquery.class.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-selector/src/jquery.selector.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-selector/src/jquery.selector.specifity.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-selector/src/jquery.selector.matches.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-concrete/src/jquery.dat.js');
		Requirements::javascript(SAPPHIRE_DIR . '/thirdparty/jquery-concrete/src/jquery.concrete.js');
		
		// javascript: custom
		Requirements::javascript(SAPPHIRE_DIR . '/javascript/CalendarDateField.js');

		// css: core
		Requirements::css(SAPPHIRE_DIR . '/thirdparty/jquery-ui-themes/smoothness/ui.all.css');
		
		// clientside config
		// TODO Abstract this into FormField to make generic configuration interface
		$jsConfig = Convert::raw2json(array(
			'minDate' => $this->futureOnly ? SSDatetime::now()->format('m/d/Y') : null
		));

		$this->addExtraClass($jsConfig);
		
		return parent::Field();
	}
	
	/**
	 * Sets the field so that only future dates can be set on them.
	 * Only applies for JavaScript value, no server-side validation.
	 * 
	 * @deprecated 2.4
	 */
	function futureDateOnly() {
		$this->futureOnly = true;
	}
	
}

?>