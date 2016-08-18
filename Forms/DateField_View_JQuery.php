<?php

namespace SilverStripe\Forms;

use SilverStripe\Control\Director;
use SilverStripe\Core\Object;
use SilverStripe\i18n\i18n;
use SilverStripe\View\Requirements;

/**
 * Preliminary API to separate optional view properties
 * like calendar popups from the actual datefield logic.
 *
 * Caution: This API is highly volatile, and might change without prior deprecation.
 */
class DateField_View_JQuery extends Object
{

	protected $field;

	/*
	 * the current jQuery UI DatePicker locale file
	 */
	protected $jqueryLocaleFile = '';

	/**
	 * @var array Maps values from {@link i18n::$all_locales} to
	 * localizations existing in jQuery UI.
	 */
	private static $locale_map = array(
		'en_GB' => 'en-GB',
		'en_US' => 'en',
		'en_NZ' => 'en-GB',
		'fr_CH' => 'fr',
		'pt_BR' => 'pt-BR',
		'sr_SR' => 'sr-SR',
		'zh_CN' => 'zh-CN',
		'zh_HK' => 'zh-HK',
		'zh_TW' => 'zh-TW',
	);

	/**
	 * @param DateField $field
	 */
	public function __construct($field)
	{
		parent::__construct();
		$this->field = $field;
	}

	/**
	 * @return DateField
	 */
	public function getField()
	{
		return $this->field;
	}

	/**
	 * Check if jQuery UI locale settings exists for the current locale
	 * @return boolean
	 */
	function regionalSettingsExist()
	{
		$lang = $this->getLang();
		$localeFile = THIRDPARTY_DIR . "/jquery-ui/datepicker/i18n/jquery.ui.datepicker-{$lang}.js";
		if (file_exists(Director::baseFolder() . '/' . $localeFile)) {
			$this->jqueryLocaleFile = $localeFile;
			return true;
		} else {
			// file goes before internal en_US settings,
			// but both will validate
			return ($lang == 'en');
		}
	}

	public function onBeforeRender()
	{
	}

	/**
	 * @param String $html
	 * @return string
	 */
	public function onAfterRender($html)
	{
		if ($this->getField()->getConfig('showcalendar')) {
			Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
			Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css');
			Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery-ui/jquery-ui.js');

			// Include language files (if required)
			if ($this->jqueryLocaleFile) {
				Requirements::javascript($this->jqueryLocaleFile);
			}

			Requirements::javascript(FRAMEWORK_DIR . "/client/dist/js/DateField.js");
		}

		return $html;
	}

	/**
	 * Determines which language to use for jQuery UI, which
	 * can be different from the value set in i18n.
	 *
	 * @return String
	 */
	protected function getLang()
	{
		$locale = $this->getField()->getLocale();
		$map = $this->config()->locale_map;
		if ($this->getField()->getConfig('jslocale')) {
			// Undocumented config property for now, might move to the jQuery view helper
			$lang = $this->getField()->getConfig('jslocale');
		} else {
			if (array_key_exists($locale, $map)) {
				// Specialized mapping for combined lang properties
				$lang = $map[$locale];
			} else {
				// Fall back to default lang (meaning "en_US" turns into "en")
				$lang = i18n::get_lang_from_locale($locale);
			}
		}

		return $lang;
	}

	/**
	 * Convert iso to jquery UI date format.
	 * Needs to be consistent with Zend formatting, otherwise validation will fail.
	 * Removes all time settings like hour/minute/second from the format.
	 * See http://docs.jquery.com/UI/Datepicker/formatDate
	 *
	 * @param String $format
	 * @return String
	 */
	public static function convert_iso_to_jquery_format($format)
	{
		$convert = array(
			'/([^d])d([^d])/' => '$1d$2',
			'/^d([^d])/' => 'd$1',
			'/([^d])d$/' => '$1d',
			'/dd/' => 'dd',
			'/SS/' => '',
			'/eee/' => 'd',
			'/e/' => 'N',
			'/D/' => '',
			'/EEEE/' => 'DD',
			'/EEE/' => 'D',
			'/w/' => '',
			// make single "M" lowercase
			'/([^M])M([^M])/' => '$1m$2',
			// make single "M" at start of line lowercase
			'/^M([^M])/' => 'm$1',
			// make single "M" at end of line lowercase
			'/([^M])M$/' => '$1m',
			// match exactly three capital Ms not preceeded or followed by an M
			'/(?<!M)MMM(?!M)/' => 'M',
			// match exactly two capital Ms not preceeded or followed by an M
			'/(?<!M)MM(?!M)/' => 'mm',
			// match four capital Ms (maximum allowed)
			'/MMMM/' => 'MM',
			'/l/' => '',
			'/YYYY/' => 'yy',
			'/yyyy/' => 'yy',
			// See http://open.silverstripe.org/ticket/7669
			'/y{1,3}/' => 'yy',
			'/a/' => '',
			'/B/' => '',
			'/hh/' => '',
			'/h/' => '',
			'/([^H])H([^H])/' => '',
			'/^H([^H])/' => '',
			'/([^H])H$/' => '',
			'/HH/' => '',
			// '/mm/' => '',
			'/ss/' => '',
			'/zzzz/' => '',
			'/I/' => '',
			'/ZZZZ/' => '',
			'/Z/' => '',
			'/z/' => '',
			'/X/' => '',
			'/r/' => '',
			'/U/' => '',
		);
		$patterns = array_keys($convert);
		$replacements = array_values($convert);

		return preg_replace($patterns, $replacements, $format);
	}
}
