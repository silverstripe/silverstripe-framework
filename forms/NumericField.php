<?php

/**
 * Text input field with validation for numeric values. Supports validating
 * the numeric value as to the {@link i18n::get_locale()} value, or an
 * overridden locale specific to this field.
 * 
 * @package forms
 * @subpackage fields-formattedinput
 */
class NumericField extends TextField {

	/**
	 * Override locale for this field
	 * 
	 * @var string
	 */
	protected $locale = null;

	public function setValue($value, $data = array()) {
		require_once "Zend/Locale/Format.php";

		// If passing in a non-string number, or a value
		// directly from a dataobject then localise this number
		if ((is_numeric($value) && !is_string($value)) || 
			($value && $data instanceof DataObject)
		){
			$locale = new Zend_Locale($this->getLocale());
			$this->value = Zend_Locale_Format::toNumber($value, array('locale' => $locale));
		} else {
			// If an invalid number, store it anyway, but validate() will fail
			$this->value = $this->clean($value);
		}
		return $this;
	}

	/**
	 * In some cases and locales, validation expects non-breaking spaces
	 *
	 * @param string $input
	 * @return string The input value, with all spaces replaced with non-breaking spaces
	 */
	protected function clean($input) {
		$nbsp = html_entity_decode('&nbsp;', null, 'UTF-8');
		return str_replace(' ', $nbsp, trim($input));
	}
	
	/**
	 * Determine if the current value is a valid number in the current locale
	 * 
	 * @return bool
	 */
	protected function isNumeric() {
		require_once "Zend/Locale/Format.php";
		$locale = new Zend_Locale($this->getLocale());
		return Zend_Locale_Format::isNumber(
			$this->clean($this->value),
			array('locale' => $locale)
		);
	}

	public function Type() {
		return 'numeric text';
	}

	public function validate($validator) {
		if(!$this->value && !$validator->fieldIsRequired($this->name)) {
			return true;
		}
		
		if($this->isNumeric()) return true;

		$validator->validationError(
			$this->name,
			_t(
				'NumericField.VALIDATION', "'{value}' is not a number, only numbers can be accepted for this field",
				array('value' => $this->value)
			),
			"validation"
		);
		return false;
	}

	/**
	 * Extracts the number value from the localised string value
	 * 
	 * @return string number value
	 */
	public function dataValue() {
		require_once "Zend/Locale/Format.php";
		if(!$this->isNumeric()) return 0;
		$locale = new Zend_Locale($this->getLocale());
		$number = Zend_Locale_Format::getNumber(
			$this->clean($this->value),
			array('locale' => $locale)
		);
		return $number;
	}
	
	/**
	 * Returns a readonly version of this field
	 */
	public function performReadonlyTransformation() {
		$field = new NumericField_Readonly($this->name, $this->title, $this->value);
		$field->setForm($this->form);
		return $field;
	}

	/**
	 * Gets the current locale this field is set to
	 * 
	 * @return string
	 */
	public function getLocale() {
		return $this->locale ?: i18n::get_locale();
	}

	/**
	 * Override the locale for this field
	 *
	 * @param string $locale
	 * @return $this
	 */
	public function setLocale($locale) {
		$this->locale = $locale;
		return $this;
	}

}

class NumericField_Readonly extends ReadonlyField {

	public function performReadonlyTransformation() {
		return clone $this;
	}

	public function Value() {
		return Convert::raw2xml($this->value ? "$this->value" : "0");
	}

}
