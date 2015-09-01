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
	 * Override locale for this field.
	 *
	 * @var string
	 */
	protected $locale = null;

	/**
	 * @param mixed $value
	 * @param array $data
	 *
	 * @return $this
	 *
	 * @throws Zend_Locale_Exception
	 */
	public function setValue($value, $data = array()) {
		require_once "Zend/Locale/Format.php";

		// If passing in a non-string number, or a value
		// directly from a DataObject then localise this number

		if(is_int($value) || is_float($value) || $data instanceof DataObject) {
			$locale = new Zend_Locale($this->getLocale());

			$this->value = Zend_Locale_Format::toNumber(
				$value,
				array('locale' => $locale)
			);
		} else {
			$this->value = $this->clean($value);
		}

		return $this;
	}

	/**
	 * In some cases and locales, validation expects non-breaking spaces.
	 *
	 * Returns the value, with all spaces replaced with non-breaking spaces.
	 *
	 * @param string $input
	 *
	 * @return string
	 */
	protected function clean($input) {
		$replacement = html_entity_decode('&nbsp;', null, 'UTF-8');

		return str_replace(' ', $replacement, trim($input));
	}

	/**
	 * Determine if the current value is a valid number in the current locale.
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

	/**
	 * {@inheritdoc}
	 */
	public function Type() {
		return 'numeric text';
	}

	/**
	 * Validate this field
	 *
	 * @param Validator $validator
	 * @return bool
	 */
	public function validate($validator) {
		if(!$this->value) {
			return true;
		}

		if($this->isNumeric()) {
			return true;
		}

		$validator->validationError(
			$this->name,
			_t(
				'NumericField.VALIDATION',
				"'{value}' is not a number, only numbers can be accepted for this field",
				array('value' => $this->value)
			),
			"validation"
		);

		return false;
	}

	/**
	 * Extracts the number value from the localised string value.
	 *
	 * @return string
	 */
	public function dataValue() {
		require_once "Zend/Locale/Format.php";

		if(!$this->isNumeric()) {
			return 0;
		}

		$locale = new Zend_Locale($this->getLocale());

		$number = Zend_Locale_Format::getNumber(
			$this->clean($this->value),
			array('locale' => $locale)
		);

		return $number;
	}

	/**
	 * Creates a read-only version of the field.
	 *
	 * @return NumericField_Readonly
	 */
	public function performReadonlyTransformation() {
		$field = new NumericField_Readonly(
			$this->name,
			$this->title,
			$this->value
		);

		$field->setForm($this->form);

		return $field;
	}

	/**
	 * Gets the current locale this field is set to.
	 *
	 * @return string
	 */
	public function getLocale() {
		if($this->locale) {
			return $this->locale;
		}

		return i18n::get_locale();
	}

	/**
	 * Override the locale for this field.
	 *
	 * @param string $locale
	 *
	 * @return $this
	 */
	public function setLocale($locale) {
		$this->locale = $locale;

		return $this;
	}
}

/**
 * Readonly version of a numeric field.
 *
 * @package forms
 * @subpackage fields-basic
 */
class NumericField_Readonly extends ReadonlyField {
	/**
	 * @return static
	 */
	public function performReadonlyTransformation() {
		return clone $this;
	}

	/**
	 * @return string
	 */
	public function Value() {
		if($this->value) {
			return Convert::raw2xml((string) $this->value);
		}

		return '0';
	}
}
