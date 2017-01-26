<?php

namespace SilverStripe\Forms;

use SilverStripe\i18n\i18n;
use SilverStripe\Security\Member;

/**
 * A simple extension to dropdown field, pre-configured to list countries.
 * It will default to the country of the current visitor.
 */
class CountryDropdownField extends DropdownField
{

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
    protected function locale()
    {
        if (($member = Member::currentUser()) && $member->Locale) {
            return $member->Locale;
        }
        return i18n::get_locale();
    }

    public function setSource($source)
    {
        if ($source) {
            return parent::setSource($source);
        }

        // Get sorted countries
        $source = i18n::getData()->i18nCountries();
        return parent::setSource($source);
    }

    public function Field($properties = array())
    {
        $source = $this->getSource();

        // Default value to best availabel locale
        $value = $this->Value();
        if ($this->config()->default_to_locale
            && (!$value || !isset($source[$value]))
            && $this->locale()
        ) {
            $value = i18n::getData()->countryFromLocale(i18n::get_locale());
            if ($value) {
                $this->setValue($value);
            }
        }

        // Default to default country otherwise
        if (!$value || !isset($source[$value])) {
            $this->setValue($this->config()->default_country);
        }

        return parent::Field($properties);
    }
}
