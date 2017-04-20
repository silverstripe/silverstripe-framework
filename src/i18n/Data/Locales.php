<?php

namespace SilverStripe\i18n\Data;

/**
 * Locales data source
 */
interface Locales
{
    /**
     * Get all country codes and names
     *
     * @return array Map of country code => name
     */
    public function getCountries();

    /**
     * Get all language codes and names
     *
     * @return array Map of language code => name
     */
    public function getLanguages();

    /**
     * Get all locale codes and names
     *
     * @return array Map of locale code => name
     */
    public function getLocales();

    /**
     * Get name of country by code
     *
     * @param string $code ISO 3166-1 country code
     * @return string
     */
    public function countryName($code);

    /**
     * Get language name for this language or locale code
     *
     * @param string $code
     * @return string
     */
    public function languageName($code);

    /**
     * Get name of locale
     *
     * @param string $locale
     * @return string
     */
    public function localeName($locale);

    /**
     * Returns the country code / suffix on any locale
     *
     * @param string $locale E.g. "en_US"
     * @return string Country code, e.g. "us"
     */
    public function countryFromLocale($locale);

    /**
     * Provides you "likely locales"
     * for a given "short" language code. This is a guess,
     * as we can't disambiguate from e.g. "en" to "en_US" - it
     * could also mean "en_UK". Based on the Unicode CLDR
     * project.
     * @see http://www.unicode.org/cldr/data/charts/supplemental/likely_subtags.html
     *
     * @param string $lang Short language code, e.g. "en"
     * @return string Long locale, e.g. "en_US"
     */
    public function localeFromLang($lang);

    /**
     * Returns the "short" language name from a locale,
     * e.g. "en_US" would return "en".
     *
     * @param string $locale E.g. "en_US"
     * @return string Short language code, e.g. "en"
     */
    public function langFromLocale($locale);

    /**
     * Validates a "long" locale format (e.g. "en_US") by checking it against {@link $locales}.
     *
     * @param string $locale
     * @return bool
     */
    public function validate($locale);

    /**
     * Returns the script direction in format compatible with the HTML "dir" attribute.
     *
     * @see http://www.w3.org/International/tutorials/bidi-xhtml/
     * @param string $locale Optional locale incl. region (underscored)
     * @return string "rtl" or "ltr"
     */
    public function scriptDirection($locale = null);
}
