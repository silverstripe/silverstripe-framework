<?php
require_once 'Zend/Date.php';

/**
 * Form field to display editable time values in an <input type="text"> field.
 *
 * # Configuration
 *
 * - 'timeformat' (string): Time format compatible with Zend_Date.
 *    Usually set to default format for {@link locale}
 *    through {@link Zend_Locale_Format::getTimeFormat()}.
 * - 'use_strtotime' (boolean): Accept values in PHP's built-in strtotime() notation, in addition
 *    to the format specified in `timeformat`. Example inputs: 'now', '11pm', '23:59:59'.
 *
 * # Localization
 *
 * See {@link DateField}
 *
 * @todo Timezone support
 *
 * @package forms
 * @subpackage fields-datetime
 */
class TimeField extends TextField {

	/**
	 * @config
	 * @var array
	 */
	private static $default_config = array(
		'timeformat' => null,
		'use_strtotime' => true,
		'datavalueformat' => 'HH:mm:ss'
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

	public function __construct($name, $title = null, $value = ""){
		if(!$this->locale) {
			$this->locale = i18n::get_locale();
		}

		$this->config = $this->config()->default_config;

		if(!$this->getConfig('timeformat')) {
			$this->setConfig('timeformat', Config::inst()->get('i18n', 'time_format'));
		}

		parent::__construct($name,$title,$value);
	}

	public function Field($properties = array()) {
		$config = array(
			'timeformat' => $this->getConfig('timeformat')
		);
		$config = array_filter($config);
		$this->addExtraClass(Convert::raw2json($config));
		return parent::Field($properties);
	}

	public function Type() {
		return 'time text';
	}

	/**
	 * Parses a time into a Zend_Date object
	 *
	 * @param string $value Raw value
	 * @param string $format Format string to check against
	 * @param string $locale Optional locale to parse against
	 * @param boolean $exactMatch Flag indicating that the date must be in this
	 * exact format, and is unchanged after being parsed and written out
	 *
	 * @return Zend_Date Returns the Zend_Date, or null if not in the specified format
	 */
	protected function parseTime($value, $format, $locale = null, $exactMatch = false) {
		// Check if the date is in the correct format
		if(!Zend_Date::isDate($value, $format)) return null;

		// Parse the value
		$valueObject = new Zend_Date($value, $format, $locale);

		// For exact matches, ensure the value preserves formatting after conversion
		if($exactMatch && ($value !== $valueObject->get($format))) {
			return null;
		} else {
			return $valueObject;
		}
	}


	/**
	 * Sets the internal value to ISO date format.
	 *
	 * @param String|Array $val
	 */
	public function setValue($val) {

		// Fuzzy matching through strtotime() to support a wider range of times,
		// e.g. 11am. This means that validate() might not fire.
		// Note: Time formats are assumed to be less ambiguous than dates across locales.
		if($this->getConfig('use_strtotime') && !empty($val)) {
			if($parsedTimestamp = strtotime($val)) {
				$parsedObj = new Zend_Date($parsedTimestamp, Zend_Date::TIMESTAMP);
				$val = $parsedObj->get($this->getConfig('timeformat'));
				unset($parsedObj);
			}
		}

		if(empty($val)) {
			$this->value = null;
			$this->valueObj = null;
		}
		// load ISO time from database (usually through Form->loadDataForm())
		// Requires exact format to prevent false positives from locale specific times
		else if($this->valueObj = $this->parseTime($val, $this->getConfig('datavalueformat'), null, true)) {
			$this->value = $this->valueObj->get($this->getConfig('timeformat'));
		}
		// Set in current locale (as string)
		else if($this->valueObj = $this->parseTime($val, $this->getConfig('timeformat'), $this->locale)) {
			$this->value = $this->valueObj->get($this->getConfig('timeformat'));
		}
		// Fallback: Set incorrect value so validate() can pick it up
		elseif(is_string($val)) {
			$this->value = $val;
			$this->valueObj = null;
		}
		else {
			$this->value = null;
			$this->valueObj = null;
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

	/**
	 * Validate this field
	 *
	 * @param Validator $validator
	 * @return bool
	 */
	public function validate($validator) {

		// Don't validate empty fields
		if(empty($this->value)) return true;

		if(!Zend_Date::isDate($this->value, $this->getConfig('timeformat'), $this->locale)) {
			$validator->validationError(
				$this->name,
				_t(
					'TimeField.VALIDATEFORMAT', "Please enter a valid time format ({format})",
					array('format' => $this->getConfig('timeformat'))
				),
				"validation",
				false
			);
			return false;
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

	/**
	 * Creates a new readonly field specified below
	 */
	public function performReadonlyTransformation() {
		return $this->castedCopy('TimeField_Readonly');
	}

	public function castedCopy($class) {
		$copy = parent::castedCopy($class);
		if($copy->hasMethod('setConfig')) {
			$config = $this->getConfig();
			foreach($config as $k => $v) {
				$copy->setConfig($k, $v);
			}
		}

		return $copy;
	}

}

/**
 * The readonly class for our {@link TimeField}.
 *
 * @package forms
 * @subpackage fields-datetime
 */
class TimeField_Readonly extends TimeField {

	protected $readonly = true;

	public function Field($properties = array()) {
		if($this->valueObj) {
			$val = Convert::raw2xml($this->valueObj->toString($this->getConfig('timeformat')));
		} else {
			// TODO Localization
			$val = '<i>(not set)</i>';
		}

		return "<span class=\"readonly\" id=\"" . $this->id() . "\">$val</span>";
	}
}
