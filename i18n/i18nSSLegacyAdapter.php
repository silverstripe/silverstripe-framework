<?php
require_once 'Zend/Locale.php';
require_once 'Zend/Translate/Adapter.php';

/**
 * @package framework
 * @subpackage i18n
 */
class i18nSSLegacyAdapter extends Zend_Translate_Adapter implements i18nTranslateAdapterInterface {

	/**
	 * Generates the adapter
	 *
	 * @param  array|Zend_Config $options Translation content
	 */
	public function __construct($options = array()) {
		$this->_options['keyDelimiter'] = ".";
		parent::__construct($options);
	}

	protected function _loadTranslationData($data, $locale, array $options = array()) {
		$options = array_merge($this->_options, $options);

		if ($options['clear']  ||  !isset($this->_translate[$locale])) {
			$this->_translate[$locale] = array();
		}

		if(is_array($data)) return array($locale => $data);

		$this->_filename = $data;

		// Ignore files with other extensions
		if(pathinfo($this->_filename, PATHINFO_EXTENSION) != 'php') return;

		if (!is_readable($this->_filename)) {
			require_once 'Zend/Translate/Exception.php';
			throw new Zend_Translate_Exception('Error opening translation file \'' . $this->_filename . '\'.');
		}

		global $lang;
		if(!isset($lang['en_US'])) $lang['en_US'] = array();
		// TODO Diff locale array to avoid re-parsing all previous translations whenever a new module is included.
		require_once($this->_filename);

		$flattened = array();
		if($lang[$locale]) {
			$iterator = new i18nSSLegacyAdapter_Iterator(new RecursiveArrayIterator($lang[$locale]));
			foreach($iterator as $k => $v) {
				$flattenedKey = implode($options['keyDelimiter'], array_filter($iterator->getKeyStack()));
				$flattened[$flattenedKey] = (is_array($v)) ? $v[0] : $v;
			}
		}

		return array($locale => $flattened);
	}

	public function toString() {
		return "i18nSSLegacy";
	}

	public function getFilenameForLocale($locale) {
		return "{$locale}.php";
	}

}

/**
 * @package framework
 * @subpackage i18n
 */
class i18nSSLegacyAdapter_Iterator extends RecursiveIteratorIterator {

	protected $keyStack = array();

	public function callGetChildren() {
		$this->keyStack[] = parent::key();
		return parent::callGetChildren();
	}

	public function endChildren() {
		array_pop($this->keyStack);
		parent::endChildren();
	}

	public function key() {
		return json_encode($this->getKeyStack());
	}

	public function getKeyStack() {
		return array_merge($this->keyStack, array(parent::key()));
	}
}
