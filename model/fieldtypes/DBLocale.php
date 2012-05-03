<?php
/**
 * Locale database field, mainly used in {@link Translatable} extension.
 * 
 * @todo Allowing showing locale values in different languages through Nice()
 * 
 * @package framework
 * @subpackage i18n
 */
class DBLocale extends Varchar {
	
	function __construct($name, $size = 16) {
		parent::__construct($name, $size);
	}

	/**
	 * See {@link getShortName()} and {@link getNativeName()}.
	 * 
	 * @param Boolean $showNative Show a localized version of the name instead, based on the 
	 *  field's locale value.
	 * @return String
	 */
	function Nice($showNative=false) {
		if ($showNative) {
			return $this->getNativeName();
		}
		return $this->getShortName();
	}
	
	function RFC1766() {
		return i18n::convert_rfc1766($this->value);
	}
	
	/**
	 * Resolves the locale to a common english-language
	 * name through {@link i18n::get_common_locales()}.
	 * 
	 * @return String
	 */
	function getShortName() {
		$common_names = i18n::get_common_locales();
		return (isset($common_names[$this->value])) ? $common_names[$this->value] : false;
	}
	
	/**
	 * @return String
	 */
	function getLongName() {
		return i18n::get_locale_name($this->value);
	}

	/**
	 * Returns the localized name based on the field's value.
	 * Example: "de_DE" returns "Deutsch".
	 * 
	 * @return String
	 */
	function getNativeName() {
		$common_names = i18n::get_common_locales(true);
		return (isset($common_names[$this->value])) ? $common_names[$this->value] : false;
	}
}

