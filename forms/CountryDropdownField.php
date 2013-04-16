<?php

/**
 * A simple extension to dropdown field, pre-configured to list countries.
 * It will default to the country of the current visitor.
 *
 * @package forms
 * @subpackage fields-relational
 */
class CountryDropdownField extends DropdownField {

	/**
	 * Should we default the dropdown to the region determined from the user's locale?
	 * @var bool
	 */
	private static $default_to_locale = true;

	/**
	 * The region code to default to if default_to_locale is set to false, or we can't determine a region from a locale
	 *  @var string
	 */
	private static $default_country = 'NZ';

	protected $extraClasses = array('dropdown');

	/**
	 * Get the locale of the Member, or if we're not logged in or don't have a locale, use the default one
	 * @return string
	 */
	protected function locale() {
		if (($member = Member::currentUser()) && $member->Locale) return $member->Locale;
		return i18n::get_locale();
	}

	public function __construct($name, $title = null, $source = null, $value = "", $form=null) {
		if(!is_array($source)) {
			// Get a list of countries from Zend
			$source = Zend_Locale::getTranslationList('territory', $this->locale(), 2);

			// We want them ordered by display name, not country code

			// PHP 5.3 has an extension that sorts UTF-8 strings correctly
			if (class_exists('Collator') && ($collator = Collator::create($this->locale()))) {
				$collator->asort($source);
			}
			// Otherwise just put up with them being weirdly ordered for now
			else {
				asort($source);
			}

			// We don't want "unknown country" as an option
			unset($source['ZZ']);
		}

		parent::__construct($name, ($title===null) ? $name : $title, $source, $value, $form);
	}

	public function Field($properties = array()) {
		$source = $this->getSource();

		if (!$this->value || !isset($source[$this->value])) {
			if ($this->config()->default_to_locale && $this->locale()) {
				$locale = new Zend_Locale();
				$locale->setLocale($this->locale());
				$this->value = $locale->getRegion();
			}
		}

		if (!$this->value || !isset($source[$this->value])) {
			$this->value = $this->config()->default_country;
		}

		return parent::Field();
	}
}
