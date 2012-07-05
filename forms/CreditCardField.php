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
	
	function Field($properties = array()) {
		$parts = $this->value;
		if(!is_array($parts)) $parts = explode("\n", chunk_split($parts,4,"\n"));
		$parts = array_pad($parts, 4, "");

		// TODO Mark as disabled/readonly
		$field = "<span id=\"{$this->name}_Holder\" class=\"creditCardField\">" .
				"<input autocomplete=\"off\" name=\"{$this->name}[0]\" value=\"$parts[0]\" maxlength=\"4\"" . $this->getTabIndexHTML(0) . " /> - " .
				"<input autocomplete=\"off\" name=\"{$this->name}[1]\" value=\"$parts[1]\" maxlength=\"4\"" . $this->getTabIndexHTML(1) . " /> - " .
				"<input autocomplete=\"off\" name=\"{$this->name}[2]\" value=\"$parts[2]\" maxlength=\"4\"" . $this->getTabIndexHTML(2) . " /> - " .
				"<input autocomplete=\"off\" name=\"{$this->name}[3]\" value=\"$parts[3]\" maxlength=\"4\"" . $this->getTabIndexHTML(3) . " /></span>";
		return $field;
	}

	/**
	 * Get tabindex HTML string
	 *
	 * @param int $increment Increase current tabindex by this value
	 * @return string
	 */
	protected function getTabIndexHTML($increment = 0) {
		$tabIndex = (int)$this->getAttribute('tabindex') + (int)$increment;
		return (is_numeric($tabIndex)) ? ' tabindex = "' . $tabIndex . '"' : '';
	}
	
	function dataValue() {
		if(is_array($this->value)) return implode("", $this->value);
		else return $this->value;
	}
	
	function validate($validator){
		// If the field is empty then don't return an invalidation message
		if(!trim(implode("", $this->value))) return true;
		
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
