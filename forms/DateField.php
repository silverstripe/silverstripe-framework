<?php
require_once 'Zend/Date.php';

/**
 * Form field to display an editable date string,
 * either in a single `<input type="text">` field,
 * or in three separate fields for day, month and year.
 *
 * # Configuration
 *
 * - 'showcalendar' (boolean): Determines if a calendar picker is shown.
 *    By default, jQuery UI datepicker is used (see {@link DateField_View_JQuery}).
 * - 'jslocale' (string): Overwrites the "Locale" value set in this class.
 *    Only useful in combination with {@link DateField_View_JQuery}.
 * - 'dmyfields' (boolean): Show three input fields for day, month and year separately.
 *    CAUTION: Might not be useable in combination with 'showcalendar', depending on the used javascript library
 * - 'dmyseparator' (string): HTML markup to separate day, month and year fields.
 *    Only applicable with 'dmyfields'=TRUE. Use 'dateformat' to influence date representation with 'dmyfields'=FALSE.
 * - 'dmyplaceholders': Show HTML5 placehoder text to allow identification of the three separate input fields
 * - 'dateformat' (string): Date format compatible with Zend_Date.
 *    Usually set to default format for {@link locale} through {@link Zend_Locale_Format::getDateFormat()}.
 * - 'datavalueformat' (string): Internal ISO format string used by {@link dataValue()} to save the
 *    date to a database.
 * - 'min' (string): Minimum allowed date value (in ISO format, or strtotime() compatible).
 *    Example: '2010-03-31', or '-7 days'
 * - 'max' (string): Maximum allowed date value (in ISO format, or strtotime() compatible).
 *    Example: '2010-03-31', or '1 year'
 *
 * Depending which UI helper is used, further namespaced configuration options are available.
 * For the default jQuery UI, all options prefixed/namespaced with "jQueryUI." will be respected as well.
 * Example: <code>$myDateField->setConfig('jQueryUI.showWeek', true);</code>
 * See http://docs.jquery.com/UI/Datepicker for details.
 *
 * # Localization
 *
 * The field will get its default locale from {@link i18n::get_locale()}, and set the `dateformat`
 * configuration accordingly. Changing the locale through {@link setLocale()} will not update the
 * `dateformat` configuration automatically.
 *
 * See http://doc.silverstripe.org/framework/en/topics/i18n for more information about localizing form fields.
 *
 * # Usage
 *
 * ## Example: German dates with separate fields for day, month, year
 *
 *   $f = new DateField('MyDate');
 *   $f->setLocale('de_DE');
 *   $f->setConfig('dmyfields', true);
 *
 * # Validation
 *
 * Caution: JavaScript validation is only supported for the 'en_NZ' locale at the moment,
 * it will be disabled automatically for all other locales.
 *
 * @package forms
 * @subpackage fields-datetime
 */
class DateField extends TextField {

	/**
	 * @config
	 * @var array
	 */
	private static $default_config = array(
		'showcalendar' => false,
		'jslocale' => null,
		'dmyfields' => false,
		'dmyseparator' => '&nbsp;<span class="separator">/</span>&nbsp;',
		'dmyplaceholders' => true,
		'dateformat' => null,
		'datavalueformat' => 'yyyy-MM-dd',
		'min' => null,
		'max' => null,
	);

	/**
	 * @var array
	 */
	protected $config;

	/**
	 * @var String
	 */
	protected $locale = null;

	/**
	 * @var Zend_Date Just set if the date is valid.
	 * {@link $value} will always be set to aid validation,
	 * and might contain invalid values.
	 */
	protected $valueObj = null;

	public function __construct($name, $title = null, $value = null) {
		if(!$this->locale) {
			$this->locale = i18n::get_locale();
		}

		$this->config = $this->config()->default_config;
		if(!$this->getConfig('dateformat')) {
			$this->setConfig('dateformat', Config::inst()->get('i18n', 'date_format'));
		}

		foreach ($this->config()->default_config AS $defaultK => $defaultV) {
			if ($defaultV) {
				if ($defaultK=='locale')
					$this->locale = $defaultV;
				else
					$this->setConfig($defaultK, $defaultV);
			}
		}

		parent::__construct($name, $title, $value);
	}

	public function FieldHolder($properties = array()) {
		if ($this->getConfig('showcalendar')) {
			// TODO Replace with properly extensible view helper system
			$d = DateField_View_JQuery::create($this);
			if(!$d->regionalSettingsExist()) {
				$dateformat = $this->getConfig('dateformat');

				// if no localefile is present, the jQuery DatePicker
				// month- and daynames will default to English, so the date
				// will not pass Zend validatiobn. We provide a fallback
				if (preg_match('/(MMM+)|(EEE+)/', $dateformat)) {
					$this->setConfig('dateformat', $this->getConfig('datavalueformat'));
				}
			}
			$d->onBeforeRender();
		}
		$html = parent::FieldHolder();

		if(!empty($d)) {
			$html = $d->onAfterRender($html);
		}
		return $html;
	}

	function SmallFieldHolder($properties = array()){
		$d = DateField_View_JQuery::create($this);
		$d->onBeforeRender();
		$html = parent::SmallFieldHolder($properties);
		$html = $d->onAfterRender($html);
		return $html;
	}

	public function Field($properties = array()) {
		$config = array(
			'showcalendar' => $this->getConfig('showcalendar'),
			'isoDateformat' => $this->getConfig('dateformat'),
			'jquerydateformat' => DateField_View_JQuery::convert_iso_to_jquery_format($this->getConfig('dateformat')),
			'min' => $this->getConfig('min'),
			'max' => $this->getConfig('max')
		);

		// Add other jQuery UI specific, namespaced options (only serializable, no callbacks etc.)
		// TODO Move to DateField_View_jQuery once we have a properly extensible HTML5 attribute system for FormField
		$jqueryUIConfig = array();
		foreach($this->getConfig() as $k => $v) {
			if(preg_match('/^jQueryUI\.(.*)/', $k, $matches)) $jqueryUIConfig[$matches[1]] = $v;
		}
		if ($jqueryUIConfig)
			$config['jqueryuiconfig'] =  Convert::array2json(array_filter($jqueryUIConfig));
		$config = array_filter($config);
		foreach($config as $k => $v) $this->setAttribute('data-' . $k, $v);

		// Three separate fields for day, month and year
		if($this->getConfig('dmyfields')) {
			// values
			$valArr = ($this->valueObj) ? $this->valueObj->toArray() : null;

			// fields
			$fieldNames = Zend_Locale::getTranslationList('Field', $this->locale);
			$fieldDay = NumericField::create($this->name . '[day]', false, ($valArr) ? $valArr['day'] : null)
				->addExtraClass('day')
				->setAttribute('placeholder', $this->getConfig('dmyplaceholders') ? $fieldNames['day'] : null)
				->setMaxLength(2);

			$fieldMonth = NumericField::create($this->name . '[month]', false, ($valArr) ? $valArr['month'] : null)
				->addExtraClass('month')
				->setAttribute('placeholder', $this->getConfig('dmyplaceholders') ? $fieldNames['month'] : null)
				->setMaxLength(2);

			$fieldYear = NumericField::create($this->name . '[year]', false, ($valArr) ? $valArr['year'] : null)
				->addExtraClass('year')
				->setAttribute('placeholder', $this->getConfig('dmyplaceholders') ? $fieldNames['year'] : null)
				->setMaxLength(4);

			// order fields depending on format
			$sep = $this->getConfig('dmyseparator');
			$format = $this->getConfig('dateformat');
			$fields = array();
			$fields[stripos($format, 'd')] = $fieldDay->Field();
			$fields[stripos($format, 'm')] = $fieldMonth->Field();
			$fields[stripos($format, 'y')] = $fieldYear->Field();
			ksort($fields);
			$html = implode($sep, $fields);

			// dmyfields doesn't work with showcalendar
			$this->setConfig('showcalendar',false);
		}
		// Default text input field
		else {
			$html = parent::Field();
		}

		return $html;
	}

	public function Type() {
		return 'date text';
	}

	/**
	 * Sets the internal value to ISO date format.
	 *
	 * @param String|Array $val
	 */
	public function setValue($val) {
		$locale = new Zend_Locale($this->locale);

		if(empty($val)) {
			$this->value = null;
			$this->valueObj = null;
		} else {
			if($this->getConfig('dmyfields')) {
				// Setting in correct locale
				if(is_array($val) && $this->validateArrayValue($val)) {
					// set() gets confused with custom date formats when using array notation
					if(!(empty($val['day']) || empty($val['month']) || empty($val['year']))) {
						$this->valueObj = new Zend_Date($val, null, $locale);
						$this->value = $this->valueObj->toArray();
					} else {
						$this->value = $val;
						$this->valueObj = null;
					}
				}
				// load ISO date from database (usually through Form->loadDataForm())
				else if(!empty($val) && Zend_Date::isDate($val, $this->getConfig('datavalueformat'), $locale)) {
					$this->valueObj = new Zend_Date($val, $this->getConfig('datavalueformat'), $locale);
					$this->value = $this->valueObj->toArray();
				}
				else {
					$this->value = $val;
					$this->valueObj = null;
				}
			} else {
				// Setting in corect locale.
				// Caution: Its important to have this check *before* the ISO date fallback,
				// as some dates are falsely detected as ISO by isDate(), e.g. '03/04/03'
				// (en_NZ for 3rd of April, definetly not yyyy-MM-dd)
				if(!empty($val) && Zend_Date::isDate($val, $this->getConfig('dateformat'), $locale)) {
					$this->valueObj = new Zend_Date($val, $this->getConfig('dateformat'), $locale);
					$this->value = $this->valueObj->get($this->getConfig('dateformat'), $locale);

				}
				// load ISO date from database (usually through Form->loadDataForm())
				else if(!empty($val) && Zend_Date::isDate($val, $this->getConfig('datavalueformat'))) {
					$this->valueObj = new Zend_Date($val, $this->getConfig('datavalueformat'));
					$this->value = $this->valueObj->get($this->getConfig('dateformat'), $locale);
				}
				else {
					$this->value = $val;
					$this->valueObj = null;
				}
			}
		}

		return $this;
	}

	/**
	 * @return String ISO 8601 date, suitable for insertion into database
	 */
	public function dataValue() {
		if($this->valueObj) {
			return $this->valueObj->toString($this->getConfig('datavalueformat'));
		} else {
			return null;
		}
	}

	public function performReadonlyTransformation() {
		$field = $this->castedCopy('DateField_Disabled');
		$field->setValue($this->dataValue());
		$field->readonly = true;

		return $field;
	}

	public function castedCopy($class) {
		$copy = new $class($this->name);
		if($copy->hasMethod('setConfig')) {
			$config = $this->getConfig();
			foreach($config as $k => $v) {
				$copy->setConfig($k, $v);
			}
		}

		return parent::castedCopy($copy);
	}

	/**
	 * Validate an array with expected keys 'day', 'month' and 'year.
	 * Used because Zend_Date::isDate() doesn't provide this.
	 *
	 * @param Array $val
	 * @return boolean
	 */
	public function validateArrayValue($val) {
		if(!is_array($val)) return false;

		// Validate against Zend_Date,
		// but check for empty array keys (they're included in standard form submissions)
		return (
			array_key_exists('year', $val)
			&& (!$val['year'] || Zend_Date::isDate($val['year'], 'yyyy', $this->locale))
			&& array_key_exists('month', $val)
			&& (!$val['month'] || Zend_Date::isDate($val['month'], 'MM', $this->locale))
			&& array_key_exists('day', $val)
			&& (!$val['day'] || Zend_Date::isDate($val['day'], 'dd', $this->locale))
		);
	}

	/**
	 * @deprecated 4.0 Use the "DateField.default_config" config setting instead
	 * @param String $k
	 * @param mixed $v
	 * @return boolean
	 */
	public static function set_default_config($k, $v) {
		Deprecation::notice('4.0', 'Use the "DateField.default_config" config setting instead');
		return Config::inst()->update('DateField', 'default_config', array($k => $v));
	}

	/**
	 * @return Boolean
	 */
	public function validate($validator) {
		$valid = true;

		// Don't validate empty fields
		if ($this->getConfig('dmyfields')) {
			if (empty($this->value['day']) && empty($this->value['month']) && empty($this->value['year'])) {
				return $valid;
			}
		}
		elseif (empty($this->value)) {
			return $valid;
		}

		// date format
		if($this->getConfig('dmyfields')) {
			$valid = (!$this->value || $this->validateArrayValue($this->value));
		} else {
			$valid = (Zend_Date::isDate($this->value, $this->getConfig('dateformat'), $this->locale));
		}
		if(!$valid) {
			$validator->validationError(
				$this->name,
				_t(
					'DateField.VALIDDATEFORMAT2', "Please enter a valid date format ({format})",
					array('format' => $this->getConfig('dateformat'))
				),
				"validation",
				false
			);
			return false;
		}

		// min/max - Assumes that the date value was valid in the first place
		if($min = $this->getConfig('min')) {
			// ISO or strtotime()
			if(Zend_Date::isDate($min, $this->getConfig('datavalueformat'))) {
				$minDate = new Zend_Date($min, $this->getConfig('datavalueformat'));
			} else {
				$minDate = new Zend_Date(strftime('%Y-%m-%d', strtotime($min)), $this->getConfig('datavalueformat'));
			}
			if(!$this->valueObj || (!$this->valueObj->isLater($minDate) && !$this->valueObj->equals($minDate))) {
				$validator->validationError(
					$this->name,
					_t(
						'DateField.VALIDDATEMINDATE',
						"Your date has to be newer or matching the minimum allowed date ({date})",
						array('date' => $minDate->toString($this->getConfig('dateformat')))
					),
					"validation",
					false
				);
				return false;
			}
		}
		if($max = $this->getConfig('max')) {
			// ISO or strtotime()
			if(Zend_Date::isDate($min, $this->getConfig('datavalueformat'))) {
				$maxDate = new Zend_Date($max, $this->getConfig('datavalueformat'));
			} else {
				$maxDate = new Zend_Date(strftime('%Y-%m-%d', strtotime($max)), $this->getConfig('datavalueformat'));
			}
			if(!$this->valueObj || (!$this->valueObj->isEarlier($maxDate) && !$this->valueObj->equals($maxDate))) {
				$validator->validationError(
					$this->name,
					_t('DateField.VALIDDATEMAXDATE',
						"Your date has to be older or matching the maximum allowed date ({date})",
						array('date' => $maxDate->toString($this->getConfig('dateformat')))
					),
					"validation",
					false
				);
				return false;
			}
		}

		return true;
	}

	/**
	 * @return string
	 */
	public function getLocale() {
		return $this->locale;
	}

	/**
	 * Caution: Will not update the 'dateformat' config value.
	 *
	 * @param String $locale
	 */
	public function setLocale($locale) {
		$this->locale = $locale;
		return $this;
	}

	/**
	 * @param string $name
	 * @param mixed $val
	 */
	public function setConfig($name, $val) {
		switch($name) {
			case 'min':
				$format = $this->getConfig('datavalueformat');
				if($val && !Zend_Date::isDate($val, $format) && !strtotime($val)) {
					throw new InvalidArgumentException(
						sprintf('Date "%s" is not a valid minimum date format (%s) or strtotime() argument',
						$val, $format));
				}
				break;
			case 'max':
				$format = $this->getConfig('datavalueformat');
				if($val && !Zend_Date::isDate($val, $format) && !strtotime($val)) {
					throw new InvalidArgumentException(
						sprintf('Date "%s" is not a valid maximum date format (%s) or strtotime() argument',
						$val, $format));
				}
				break;
		}

		$this->config[$name] = $val;
		return $this;
	}

	/**
	 * @param String $name Optional, returns the whole configuration array if empty
	 * @return mixed|array
	 */
	public function getConfig($name = null) {
		if($name) {
			return isset($this->config[$name]) ? $this->config[$name] : null;
		} else {
			return $this->config;
		}
	}
}

/**
 * Disabled version of {@link DateField}.
 * Allows dates to be represented in a form, by showing in a user friendly format, eg, dd/mm/yyyy.
 * @package forms
 * @subpackage fields-datetime
 */
class DateField_Disabled extends DateField {

	protected $disabled = true;

	public function Field($properties = array()) {
		if($this->valueObj) {
			if($this->valueObj->isToday()) {
				$val = Convert::raw2xml($this->valueObj->toString($this->getConfig('dateformat'))
					. ' ('._t('DateField.TODAY','today').')');
			} else {
				$df = new Date($this->name);
				$df->setValue($this->dataValue());
				$val = Convert::raw2xml($this->valueObj->toString($this->getConfig('dateformat'))
					. ', ' . $df->Ago());
			}
		} else {
			$val = '<i>('._t('DateField.NOTSET', 'not set').')</i>';
		}

		return "<span class=\"readonly\" id=\"" . $this->id() . "\">$val</span>";
	}

	public function Type() {
		return "date_disabled readonly";
	}
}

/**
 * Preliminary API to separate optional view properties
 * like calendar popups from the actual datefield logic.
 *
 * Caution: This API is highly volatile, and might change without prior deprecation.
 *
 * @package framework
 * @subpackage forms
 */
class DateField_View_JQuery extends Object {

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
	public function __construct($field) {
		$this->field = $field;
	}

	/**
	 * @return DateField
	 */
	public function getField() {
		return $this->field;
	}

	/**
	 * Check if jQuery UI locale settings exists for the current locale
	 * @return boolean
	 */
	function regionalSettingsExist() {
		$lang = $this->getLang();
		$localeFile = THIRDPARTY_DIR . "/jquery-ui/datepicker/i18n/jquery.ui.datepicker-{$lang}.js";
		if (file_exists(Director::baseFolder() . '/' .$localeFile)){
			$this->jqueryLocaleFile = $localeFile;
			return true;
		} else {
			// file goes before internal en_US settings,
			// but both will validate
			return ($lang == 'en');
		}
	}

	public function onBeforeRender() {
	}

	/**
	 * @param String $html
	 * @return
	 */
	public function onAfterRender($html) {
		if($this->getField()->getConfig('showcalendar')) {
			Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
			Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css');
			Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery-ui/jquery-ui.js');

			// Include language files (if required)
			if ($this->jqueryLocaleFile){
				Requirements::javascript($this->jqueryLocaleFile);
			}

			Requirements::javascript(FRAMEWORK_DIR . "/javascript/DateField.js");
		}

		return $html;
	}

	/**
	 * Determines which language to use for jQuery UI, which
	 * can be different from the value set in i18n.
	 *
	 * @return String
	 */
	protected function getLang() {
		$locale = $this->getField()->getLocale();
		$map = $this->config()->locale_map;
		if($this->getField()->getConfig('jslocale')) {
			// Undocumented config property for now, might move to the jQuery view helper
			$lang = $this->getField()->getConfig('jslocale');
		} else if(array_key_exists($locale, $map)) {
			// Specialized mapping for combined lang properties
			$lang = $map[$locale];
		} else {
			// Fall back to default lang (meaning "en_US" turns into "en")
			$lang = i18n::get_lang_from_locale($locale);
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
	public static function convert_iso_to_jquery_format($format) {
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

