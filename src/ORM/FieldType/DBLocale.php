<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\i18n\i18n;

/**
 * Locale database field
 */
class DBLocale extends DBVarchar
{

    public function __construct($name = null, $size = 16)
    {
        parent::__construct($name, $size);
    }

    /**
     * See {@link getShortName()} and {@link getNativeName()}.
     *
     * @param Boolean $showNative Show a localized version of the name instead, based on the
     *  field's locale value.
     * @return String
     */
    public function Nice($showNative = false)
    {
        if ($showNative) {
            return $this->getNativeName();
        }
        return $this->getShortName();
    }

    public function RFC1766()
    {
        return i18n::convert_rfc1766($this->value);
    }

    /**
     * Resolves the locale to a common english-language
     * name through {@link i18n::get_common_locales()}.
     *
     * @return string
     */
    public function getShortName()
    {
        return i18n::getData()->languageName($this->value);
    }

    /**
     * @return string
     */
    public function getLongName()
    {
        return i18n::getData()->localeName($this->value);
    }

    /**
     * Returns the localized name based on the field's value.
     * Example: "de_DE" returns "Deutsch".
     *
     * @return string
     */
    public function getNativeName()
    {
        $locale = $this->value;
        return i18n::with_locale($locale, function () {
            return $this->getShortName();
        });
    }
}
