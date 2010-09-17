<?php
/**
 * A composite field for date and time entry,
 * based on {@link DateField} and {@link TimeField}.
 * Usually saves into a single {@link SS_Datetime} database column.
 * If you want to save into {@link Date} or {@link Time} columns,
 * please instanciate the fields separately.
 * 
 * # Configuration
 * 
 * All options in {@link setConfig()} are passed through to {@link DateField} and {@link TimeField}.
 * 
 * @package sapphire
 * @subpackage forms
 */
class DatetimeField extends FormField {
	
	/**
	 * @var DateField
	 */
	protected $dateField = null;
	
	/**
	 * @var TimeField
	 */
	protected $timeField = null;
	
	/**
	 * @var array
	 */
	protected $config = array(
		'datavalueformat' => 'YYYY-MM-dd HH:mm:ss'
	);
		
	function __construct($name, $title = null, $value = ""){
		$this->dateField = new DateField($name . '[date]', false);
		$this->timeField = new TimeField($name . '[time]', false);
		
		parent::__construct($name, $title, $value);
	}
	
	function setForm($form) {
		parent::setForm($form);
		
		$this->dateField->setForm($form);
		$this->timeField->setForm($form);
	}
	
	function Field() {
		Requirements::css(SAPPHIRE_DIR . '/css/DatetimeField.css');
		
		return $this->dateField->FieldHolder() . $this->timeField->FieldHolder() . '<div class="clear"><!-- --></div>';
	}
	
	/**
	 * Sets the internal value to ISO date format.
	 * 
	 * @param string|array $val String expects an ISO date format. Array notation with 'date' and 'time'
	 *  keys can contain localized strings. If the 'dmyfields' option is used for {@link DateField},
	 *  the 'date' value may contain array notation was well (see {@link DateField->setValue()}).
	 */
	function setValue($val) {
		if(empty($val)) {
			$this->dateField->setValue(null);
			$this->timeField->setValue(null);
		} else {
			// String setting is only possible from the database, so we don't allow anything but ISO format
			if(is_string($val) && Zend_Date::isDate($val, $this->getConfig('datavalueformat'), $this->locale)) {
				// split up in date and time string values. 
				$valueObj = new Zend_Date($val, $this->getConfig('datavalueformat'), $this->locale);
				// set date either as array, or as string
				if($this->dateField->getConfig('dmyfields')) {
					$this->dateField->setValue($valueObj->toArray());
				} else {
					$this->dateField->setValue($valueObj->get($this->dateField->getConfig('dateformat'), $this->locale));
				}
				// set time
				$this->timeField->setValue($valueObj->get($this->timeField->getConfig('timeformat'), $this->locale));
			}
			// Setting from form submission
			elseif(is_array($val) && array_key_exists('date', $val) && array_key_exists('time', $val)) {
				$this->dateField->setValue($val['date']);
				$this->timeField->setValue($val['time']);
			} else {
				$this->dateField->setValue($val);
				$this->timeField->setValue($val);
			}
		}
	}
	
	function dataValue() {
		$valDate = $this->dateField->dataValue();
		$valTime = $this->timeField->dataValue();

		// Only date is actually required, time is optional
		if($valDate) {
			if(!$valTime) $valTime = '00:00:00';
			return $valDate . ' ' . $valTime;
		} else {
			// TODO 
			return null;
		}
	}
	
	/**
	 * @return DateField
	 */
	function getDateField() {
		return $this->dateField;
	}
	
	/**
	 * @return TimeField
	 */
	function getTimeField() {
		return $this->timeField;
	}
	
	function setLocale($locale) {
		$this->dateField->setLocale($locale);
		$this->timeField->setLocale($locale);
	}
	
	function getLocale() {
		return $this->dateField->getLocale();
	}
	
	/**
	 * Note: Use {@link getDateField()} and {@link getTimeField()}
	 * to set field-specific config options.
	 * 
	 * @param string $name
	 * @param mixed $val
	 */
	function setConfig($name, $val) {
		$this->config[$name] = $val;
	}
	
	/**
	 * Note: Use {@link getDateField()} and {@link getTimeField()}
	 * to get field-specific config options.
	 * 
	 * @param String $name
	 * @return mixed
	 */
	function getConfig($name) {
		return $this->config[$name];
	}
	
	function validate($validator) {
		$dateValid = $this->dateField->validate($validator);
		$timeValid = $this->timeField->validate($validator);

		return ($dateValid && $timeValid);
	}
	
	function jsValidation() {
		return $this->dateField->jsValidation() . $this->timeField->jsValidation();
	}
	
	function performReadonlyTransformation() {
		$field = new DatetimeField_Readonly($this->name, $this->title, $this->dataValue());
		$field->setForm($this->form);
		
		return $field;
	}
}

/**
 * The readonly class for our {@link DatetimeField}.
 * 
 * @package forms
 * @subpackage fields-datetime
 */
class DatetimeField_Readonly extends DatetimeField {
	
	protected $readonly = true;
		
	function Field() {
		$valDate = $this->dateField->dataValue();
		$valTime = $this->timeField->dataValue();
		if($valDate && $valTime) {
			$format = $this->dateField->getConfig('dateformat') . ' ' . $this->timeField->getConfig('timeformat');
			$valueObj = new Zend_Date(
				$valDate . ' ' . $valTime, 
				$this->getConfig('datavalueformat'), 
				$this->dateField->getLocale()
			);
			$val = $valueObj->toString($format);
		} else {
			// TODO Localization
			$val = '<i>(not set)</i>';
		}
		
		return "<span class=\"readonly\" id=\"" . $this->id() . "\">$val</span>";
	}
	
	function jsValidation() {
		return null;
	}
	
	function validate($validator) {
		return true;	
	}
}
?>