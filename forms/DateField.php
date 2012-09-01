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
 * See http://doc.silverstripe.org/sapphire/en/topics/i18n for more information about localizing form fields.
 * 
 * # Usage
 * 
 * ## Example: German dates with separate fields for day, month, year
 * 
 * 	$f = new DateField('MyDate');
 * 	$f->setLocale('de_DE');
 * 	$f->setConfig('dmyfields');
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
	 * @var array
	 */
	static $default_config = array(
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
	
	function __construct($name, $title = null, $value = null) {
		if(!$this->locale) {
			$this->locale = i18n::get_locale();
		}
		
		$this->config = self::$default_config;
		
		if(!$this->getConfig('dateformat')) {
			$this->setConfig('dateformat', i18n::get_date_format());
		}
		
		foreach (self::$default_config AS $defaultK => $defaultV) {
			if ($defaultV) {
				if ($defaultK=='locale')
					$this->locale = $defaultV;
				else
					$this->setConfig($defaultK, $defaultV);
			}
		}

		parent::__construct($name, $title, $value);
	}

	function FieldHolder($properties = array()) {
		// TODO Replace with properly extensible view helper system 
		$d = DateField_View_JQuery::create($this); 
		$d->onBeforeRender(); 
		$html = parent::FieldHolder(); 
		$html = $d->onAfterRender($html); 
		
		return $html;
	}

	function Field($properties = array()) {
		$config = array(
			'showcalendar' => $this->getConfig('showcalendar'),
			'isoDateformat' => $this->getConfig('dateformat'),
			'jqueryDateformat' => DateField_View_JQuery::convert_iso_to_jquery_format($this->getConfig('dateformat')),
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

	function Type() {
		return 'date text';
	}
		
	/**
	 * Sets the internal value to ISO date format.
	 * 
	 * @param String|Array $val 
	 */
	function setValue($val) {
		if(empty($val)) {
			$this->value = null;
			$this->valueObj = null;
		} else {
			// Quick fix for overzealous Zend validation, its case sensitive on month names (see #5990)
			if(is_string($val)) $val = ucwords(strtolower($val));
			
			if($this->getConfig('dmyfields')) {
				// Setting in correct locale
				if(is_array($val) && $this->validateArrayValue($val)) {
					// set() gets confused with custom date formats when using array notation
					if(!(empty($val['day']) || empty($val['month']) || empty($val['year']))) {
						$this->valueObj = new Zend_Date($val, null, $this->locale);
						$this->value = $this->valueObj->toArray();
					} else {
						$this->value = $val;
						$this->valueObj = null;
					}
				}
				// load ISO date from database (usually through Form->loadDataForm())
				else if(!empty($val) && Zend_Date::isDate($val, $this->getConfig('datavalueformat'), $this->locale)) {
					$this->valueObj = new Zend_Date($val, $this->getConfig('datavalueformat'), $this->locale);
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
				if(!empty($val) && Zend_Date::isDate($val, $this->getConfig('dateformat'), $this->locale)) {
					$this->valueObj = new Zend_Date($val, $this->getConfig('dateformat'), $this->locale);
					$this->value = $this->valueObj->get($this->getConfig('dateformat'), $this->locale);
					
				}
				// load ISO date from database (usually through Form->loadDataForm())
				else if(!empty($val) && Zend_Date::isDate($val, $this->getConfig('datavalueformat'))) {
					$this->valueObj = new Zend_Date($val, $this->getConfig('datavalueformat'));
					$this->value = $this->valueObj->get($this->getConfig('dateformat'), $this->locale);
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
	function dataValue() {
		if($this->valueObj) {
			return $this->valueObj->toString($this->getConfig('datavalueformat'));
		} else {
			return null;
		}
	}
	
	function performReadonlyTransformation() {
		$field = new DateField_Disabled($this->name, $this->title, $this->dataValue());
		$field->setForm($this->form);
		$field->readonly = true;
		
		return $field;
	}

	/**
	 * Validate an array with expected keys 'day', 'month' and 'year.
	 * Used because Zend_Date::isDate() doesn't provide this.
	 * 
	 * @param Array $val
	 * @return boolean
	 */
	function validateArrayValue($val) {
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
	 * @param String $k
	 * @param mixed $v
	 * @return boolean
	 */
	static function set_default_config($k, $v) {
	  if (array_key_exists($k,self::$default_config)) {
		self::$default_config[$k]=$v;
		return true;
	  }
	  return false;
	}

	/**
	 * @return Boolean
	 */
	function validate($validator) {
		$valid = true;
		
		// Don't validate empty fields
		if(empty($this->value)) return true;

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
			if(!$this->valueObj->isLater($minDate) && !$this->valueObj->equals($minDate)) {
				$validator->validationError(
					$this->name, 
					_t(
						'DateField.VALIDDATEMINDATE', "Your date has to be newer or matching the minimum allowed date ({date})", 
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
			if(!$this->valueObj->isEarlier($maxDate) && !$this->valueObj->equals($maxDate)) {
				$validator->validationError(
					$this->name, 
					_t(
						'DateField.VALIDDATEMAXDATE', "Your date has to be older or matching the maximum allowed date ({date})", 
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
	function getLocale() {
		return $this->locale;
	}
	
	/**
	 * Caution: Will not update the 'dateformat' config value.
	 * 
	 * @param String $locale
	 */
	function setLocale($locale) {
		$this->locale = $locale;
		return $this;
	}
	
	/**
	 * @param string $name
	 * @param mixed $val
	 */
	function setConfig($name, $val) {
		switch($name) {
			case 'min':
				$format = $this->getConfig('datavalueformat');
				if($val && !Zend_Date::isDate($val, $format) && !strtotime($val)) {
					throw new InvalidArgumentException('Date "%s" is not a valid minimum date format (%s) or strtotime() argument', $val, $format);
				}
				break;
			case 'max':
				$format = $this->getConfig('datavalueformat');
				if($val && !Zend_Date::isDate($val, $format) && !strtotime($val)) {
					throw new InvalidArgumentException('Date "%s" is not a valid maximum date format (%s) or strtotime() argument', $val, $format);
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
	function getConfig($name = null) {
		return $name ? $this->config[$name] : $this->config;
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
		
	function Field($properties = array()) {
		if($this->valueObj) {
			if($this->valueObj->isToday()) {
				$val = Convert::raw2xml($this->valueObj->toString($this->getConfig('dateformat')) . ' ('._t('DateField.TODAY','today').')');
			} else {
				$df = new Date($this->name);
				$df->setValue($this->dataValue());
				$val = Convert::raw2xml($this->valueObj->toString($this->getConfig('dateformat')) . ', ' . $df->Ago());
			}
		} else {
			$val = '<i>('._t('DateField.NOTSET', 'not set').')</i>';
		}
		
		return "<span class=\"readonly\" id=\"" . $this->id() . "\">$val</span>";
	}
	
	function Type() { 
		return "date_disabled readonly";
	}
	
	function validate($validator) {
		return true;	
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
	
	/**
	 * @var array Maps values from {@link i18n::$all_locales()} to 
	 * localizations existing in jQuery UI.
	 */
	static $locale_map = array(
		'en_GB' => 'en-GB',
		'en_US' => 'en', 
		'en_NZ' => 'en-GB', 
		'fr_CH' => 'fr-CH',
		'pt_BR' => 'pt-BR',
		'sr_SR' => 'sr-SR',
		'zh_CN' => 'zh-CN',
		'zh_HK' => 'zh-HK',
		'zh_TW' => 'zh-TW',
	);
	
	/**
	 * @param DateField $field
	 */
	function __construct($field) {
		$this->field = $field;
	}
	
	/**
	 * @return DateField
	 */
	function getField() {
		return $this->field;
	}
	
	function onBeforeRender() {
	}
	
	/**
	 * @param String $html
	 * @return 
	 */
	function onAfterRender($html) {
		if($this->getField()->getConfig('showcalendar')) {
			Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
			Requirements::css(THIRDPARTY_DIR . '/jquery-ui-themes/smoothness/jquery-ui.css');
			Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery-ui/jquery-ui.js');
			
			// Include language files (if required)
			$lang = $this->getLang();
			if($lang != 'en') {
				// TODO Check for existence of locale to avoid unnecessary 404s from the CDN
				Requirements::javascript(
					sprintf(
						THIRDPARTY_DIR . '/jquery-ui/minified/i18n/jquery.ui.datepicker-%s.min.js',
						// can be a mix between names (e.g. 'de') and combined locales (e.g. 'zh-TW')
						$lang
					));
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
		if($this->getField()->getConfig('jslocale')) {
			// Undocumented config property for now, might move to the jQuery view helper
			$lang = $this->getField()->getConfig('jslocale');
		} else if(array_key_exists($locale, self::$locale_map)) {
			// Specialized mapping for combined lang properties
			$lang = self::$locale_map[$locale];
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
	static function convert_iso_to_jquery_format($format) {
		$convert = array(
			'/([^d])d([^d])/' => '$1d$2',
		  '/^d([^d])/' => 'd$1',
		  '/([^d])d$/' => '$1d',
		  '/dd/' => 'dd',
		  '/EEEE/' => 'DD',
		  '/EEE/' => 'D',
		  '/SS/' => '',
		  '/eee/' => 'd',
		  '/e/' => 'N',
		  '/D/' => '',
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

