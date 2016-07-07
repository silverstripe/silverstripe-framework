<?php

use SilverStripe\Security\Member;

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
	 *
	 * @config
	 * @var bool
	 */
	private static $default_to_locale = true;

	/**
	 * The region code to default to if default_to_locale is set to false, or we can't
	 * determine a region from a locale.
	 *
	 * @config
	 * @var string
	 */
	private static $default_country = 'NZ';

	protected $extraClasses = array('dropdown');

	protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_SINGLESELECT;

	/**
	 * Get the locale of the Member, or if we're not logged in or don't have a locale, use the default one
	 * @return string
	 */
	protected function locale() {
		if (($member = Member::currentUser()) && $member->Locale) {
			return $member->Locale;
		}
		return i18n::get_locale();
	}

	public function setSource($source) {
		if($source) {
			return parent::setSource($source);
		}

		// map empty source to country list
		// Get a list of countries from Zend
		$source = Zend_Locale::getTranslationList('territory', $this->locale(), 2);

		// We want them ordered by display name, not country code

		// PHP 5.3 has an extension that sorts UTF-8 strings correctly
		if (class_exists('Collator') && ($collator = Collator::create($this->locale()))) {
			$collator->asort($source);
		} else {
			// Otherwise just put up with them being weirdly ordered for now
			asort($source);
		}

		// We don't want "unknown country" as an option
		unset($source['ZZ']);

		return parent::setSource($source);
	}

	public function Field($properties = array()) {
		$source = $this->getSource();

		// Default value to best availabel locale
		$value = $this->Value();
		if ($this->config()->default_to_locale
			&& (!$value || !isset($source[$value]))
			&& $this->locale()
		) {
			$locale = new Zend_Locale();
			$locale->setLocale($this->locale());
			$value = $locale->getRegion();
			$this->setValue($value);
		}

		// Default to default country otherwise
		if (!$value || !isset($source[$value])) {
			$this->setValue($this->config()->default_country);
		}

		return parent::Field($properties);
	}
}
