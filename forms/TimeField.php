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
	 * @var array
	 */
	static $default_config = array(
		'timeformat' => 'HH:mm:ss',
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
		
	function __construct($name, $title = null, $value = ""){
		if(!$this->locale) {
			$this->locale = i18n::get_locale();
		}
		
		$this->config = self::$default_config;
		
		if(!$this->getConfig('timeformat')) {
			$this->setConfig('timeformat', i18n::get_time_format());
		}
		
		parent::__construct($name,$title,$value);
	}
	
	function Field($properties = array()) {
		$config = array(
			'timeformat' => $this->getConfig('timeformat')
		);
		$config = array_filter($config);
		$this->addExtraClass(Convert::raw2json($config));
		return parent::Field($properties);
	}
	
	function Type() {
		return 'time text';
	}

	/**
	 * Sets the internal value to ISO date format.
	 * 
	 * @param String|Array $val
	 */
	function setValue($val) {
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
		else if(Zend_Date::isDate($val, $this->getConfig('datavalueformat'))) {
			$this->valueObj = new Zend_Date($val, $this->getConfig('datavalueformat'));
			$this->value = $this->valueObj->get($this->getConfig('timeformat'));
		}
		// Set in current locale (as string)
		else if(Zend_Date::isDate($val, $this->getConfig('timeformat'), $this->locale)) {
			$this->valueObj = new Zend_Date($val, $this->getConfig('timeformat'), $this->locale);
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
	function dataValue() {
		if($this->valueObj) {
			return $this->valueObj->toString($this->getConfig('datavalueformat'));
		} else {
			return null;
		}
	}

	/**
	 * @return Boolean
	 */
	function validate($validator) {
		$valid = true;
		
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
	function getLocale() {
		return $this->locale;
	}
	
	/**
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
		
	/**
	 * Creates a new readonly field specified below
	 */
	function performReadonlyTransformation() {
		return new TimeField_Readonly($this->name, $this->title, $this->dataValue(), $this->getConfig('timeformat'));
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
	
	function Field($properties = array()) {
		if($this->valueObj) {
			$val = Convert::raw2xml($this->valueObj->toString($this->getConfig('timeformat')));
		} else {
			// TODO Localization
			$val = '<i>(not set)</i>';
		}
		
		return "<span class=\"readonly\" id=\"" . $this->id() . "\">$val</span>";
	}
	
	function validate($validator) {
		return true;	
	}
}
