<?php
/**
 * An extension to dropdown field, pre-configured to list languages.
 * The languages already used in the site will be on top.
 * @package forms
 * @subpackage fields-relational
 */
class LanguageDropdownField extends GroupedDropdownField {
	
	/**
	 * Create a new LanguageDropdownField
	 * @param string $name
	 * @param string $title
	 * @param array $dontInclude list of languages that won't be included
	 * @param string $translatingClass Name of the class with translated instances where to look for used languages
	 * @param string $list Indicates the source language list. Can be either Common-English, Common-Native, Locale-English, Locale-Native
	 */
	function __construct($name, $title, $dontInclude = array(), $translatingClass = 'SiteTree', $list = 'Common-English' ) {
		$usedlangs = array_diff(
			Translatable::get_existing_content_languages($translatingClass),
			$dontInclude
		);
		// we accept in dontInclude both language codes and names, so another diff is required
		$usedlangs = array_diff(
			$usedlangs,
			array_flip($dontInclude)
		);

		//if (isset($usedlangs[Translatable::default_locale()])) unset($usedlangs[Translatable::default_locale()]);
					
		if ('Common-English' == $list) $languageList = i18n::get_common_languages();
		else if ('Common-Native' == $list) $languageList = i18n::get_common_languages(true);
		else if ('Locale-English' == $list) $languageList = i18n::get_common_locales();
		else if ('Locale-Native' == $list) $languageList = i18n::get_common_locales(true);
		else $languageList = i18n::get_locale_list();

		$alllangs = array_diff(
			$languageList,
			(array)$usedlangs,
			$dontInclude
		);
		$alllangs = array_flip(array_diff(
			array_flip($alllangs),
			$dontInclude
		));
		if (isset($alllangs[Translatable::default_locale()])) unset($alllangs[Translatable::default_locale()]);

		asort($alllangs);
		if (count($usedlangs)) {
			asort($usedlangs);
			$labelAvail = _t('Form.LANGAVAIL', "Available languages");
			$labelOther = _t('Form.LANGAOTHER', "Other languages");
			parent::__construct($name, $title, array(
					$labelAvail => $usedlangs,
					$labelOther => $alllangs
				),
				reset($usedlangs)
			);
		}
		else {
			parent::__construct($name, $title, $alllangs);
		}
	}
}

?>