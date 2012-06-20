<?php
/**
 * @package framework
 * @subpackage i18n
 */

/**
 * Makes the {@link Zend_Translate_Adapter} base class aware of file naming conventions within SilverStripe.
 * Needs to be implemented by all translators used through {@link i18n::register_translator()}.
 * 
 * A bit of context: Zend is file extension agnostic by default, and simply uses the filenames to detect locales
 * with the 'scan' option, passing all files to the used adapter. We support multiple formats in the same /lang/
 * folder, so need to be more selective about including files to avoid e.g. a YAML adapter trying to parse a PHP file.
 * 
 * @see http://framework.zend.com/manual/en/zend.translate.additional.html#zend.translate.additional.combination
 */
interface i18nTranslateAdapterInterface {
	/**
	 * @param String
	 * @return String
	 */
	public function getFilenameForLocale($locale);
}