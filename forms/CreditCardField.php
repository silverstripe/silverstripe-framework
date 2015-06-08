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
		$parts = $this->value;
		if(!is_array($parts)) $parts = explode("\n", chunk_split($parts,4,"\n"));
		$parts = array_pad($parts, 4, "");

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
		if(is_array($this->value)) return implode("", $this->value);
		else return $this->value;
	}

	public function validate($validator){
		if(!$this->value || !trim(implode("", $this->value))) {
			return true;
		}

		$i=0;

		if($this->value) foreach($this->value as $part){
			if(!$part || !(strlen($part) == 4) || !preg_match("/([0-9]{4})/", $part)){
				switch($i){
					case 0: $number = _t('CreditCardField.FIRST', 'first'); break;
					case 1: $number = _t('CreditCardField.SECOND', 'second'); break;
					case 2: $number = _t('CreditCardField.THIRD', 'third'); break;
					case 3: $number = _t('CreditCardField.FOURTH', 'fourth'); break;
				}
				$validator->validationError(
					$this->name,
					_t(
						'Form.VALIDATIONCREDITNUMBER',
						"Please ensure you have entered the {number} credit card number correctly",
						array('number' => $number)
					),
					"validation",
					false
				);
				return false;
			}
		$i++;
		}
	}
}
