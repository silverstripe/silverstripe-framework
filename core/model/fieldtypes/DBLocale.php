<?php
/**
 * Locale database field, mainly used in {@link Translatable} extension.
 * 
 * @todo Allowing showing locale values in different languages through Nice()
 * 
 * @package sapphire
 * @subpackage i18n
 */
class DBLocale extends Varchar {
	
	function __construct($name, $size = 16) {
		parent::__construct($name, $size);
	}

	function Nice() {
		return $this->getShortName();
	}
	
	function RFC1766() {
		return i18n::convert_rfc1766($this->value);
	}
	
	function getShortName() {
		$common_names = i18n::get_common_locales();
		return (isset($common_names[$this->value])) ? $common_names[$this->value] : false;
	}
	
	function getLongName() {
		return i18n::get_locale_name($this->value);
	}
}
?>