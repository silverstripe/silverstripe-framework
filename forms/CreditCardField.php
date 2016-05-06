<?php
/**
 * Allows input of credit card numbers via four separate form fields,
 * including generic validation of its numeric values.
 *
 * @todo Validate
 *
 * @package forms
 * @subpackage fields-formattedinput
 */
class CreditCardField extends TextField {

	/**
	 * Add default attributes for use on all inputs.
	 *
	 * @return array List of attributes
	 */
	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array(
				'autocomplete' => 'off',
				'maxlength' => 4,
				'size' => 4
			)
		);
	}

	public function Field($properties = array()) {
		$parts = $this->arrayValue();

		$properties['ValueOne'] = $parts[0];
		$properties['ValueTwo'] = $parts[1];
		$properties['ValueThree'] = $parts[2];
		$properties['ValueFour'] = $parts[3];

		return parent::Field($properties);
	}

	/**
	 * Get tabindex HTML string
	 *
	 * @param int $increment Increase current tabindex by this value
	 * @return string
	 */
	public function getTabIndexHTML($increment = 0) {
		// we can't add a tabindex if there hasn't been one set yet.
		if($this->getAttribute('tabindex') === null) return false;

		$tabIndex = (int)$this->getAttribute('tabindex') + (int)$increment;
		return (is_numeric($tabIndex)) ? ' tabindex = "' . $tabIndex . '"' : '';
	}

	public function dataValue() {
		if(is_array($this->value)) {
			return implode("", $this->value);
		} else {
			return $this->value;
		}
	}

	/**
	 * Get either list of values, or null
	 *
	 * @return array
	 */
	public function arrayValue() {
		if (is_array($this->value)) {
			return $this->value;
		}

		$value = $this->dataValue();
		return $this->parseCreditCard($value);
	}

	/**
	 * Parse credit card value into list of four four-digit values
	 *
	 * @param string $value
	 * @return array|null
	 */
	protected function parseCreditCard($value) {
		if(preg_match("/([0-9]{4})([0-9]{4})([0-9]{4})([0-9]{4})/", $value, $parts)) {
			return [ $parts[1], $parts[2], $parts[3], $parts[4] ];
		}
		return null;
	}

	public function validate($validator){
		$value = $this->dataValue();
		if(empty($value)) {
			return true;
		}

		// Check if format is valid
		if ($this->parseCreditCard($value)) {
			return true;
		}

		// Format is invalid
		$validator->validationError(
			$this->name,
			_t(
				'Form.VALIDATIONCREDIT',
				"Please ensure you have entered the credit card number correctly"
			),
			"validation"
		);
		return false;
	}
}
