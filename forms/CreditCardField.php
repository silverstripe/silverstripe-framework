<?php
/**
 * CreditCard field, contains validation and formspec for creditcard fields.
 * @package forms
 * @subpackage fields-formattedinput
 */
class CreditCardField extends TextField {
	
	function Field() {
		$parts = explode("\n", chunk_split($this->value,4,"\n"));
		$parts = array_pad($parts, 4, "");
		$field = "<span id=\"{$this->name}_Holder\" class=\"creditCardField\">" .
				"<input autocomplete=\"off\" name=\"{$this->name}[0]\" value=\"$parts[0]\" maxlength=\"4\"" . $this->getTabIndexHTML(0) . " /> - " .
				"<input autocomplete=\"off\" name=\"{$this->name}[1]\" value=\"$parts[1]\" maxlength=\"4\"" . $this->getTabIndexHTML(1) . " /> - " .
				"<input autocomplete=\"off\" name=\"{$this->name}[2]\" value=\"$parts[2]\" maxlength=\"4\"" . $this->getTabIndexHTML(2) . " /> - " .
				"<input autocomplete=\"off\" name=\"{$this->name}[3]\" value=\"$parts[3]\" maxlength=\"4\"" . $this->getTabIndexHTML(3) . " /></span>";
		return $field;
	}
	function dataValue() {
		if(is_array($this->value)) return implode("", $this->value);
		else return $this->value;
	}
	
	function jsValidation() {
		$formID = $this->form->FormName();
		$error1 = _t('CreditCardField.VALIDATIONJS1', 'Please ensure you have entered the');
		$error2 = _t('CreditCardField.VALIDATIONJS2', 'credit card number correctly.');
		$first = _t('CreditCardField.FIRST', 'first');
		$second = _t('CreditCardField.SECOND', 'second');
		$third = _t('CreditCardField.THIRD', 'third');
		$fourth = _t('CreditCardField.FOURTH', 'fourth');
		$jsFunc =<<<JS
Behaviour.register({
	"#$formID": {
		validateCreditCard: function(fieldName) {
			if(!$(fieldName + "_Holder")) return true;
		
			// Creditcards are split into multiple values, so get the inputs from the form.
			var cardParts = $(fieldName + "_Holder").getElementsByTagName('input');
			
			var cardisnull = true;
			var i=0;
			
			for(i=0; i < cardParts.length ; i++ ){
				if(cardParts[i].value == null || cardParts[i].value == "")
					cardisnull = cardisnull && true;
				else
					cardisnull = false;
			}
			if(!cardisnull){
				// Concatenate the string values from the parts of the input.
				for(i=0; i < cardParts.length ; i++ ){
					// The creditcard number cannot be null, nor have less than 4 digits.
					if(
						cardParts[i].value == null || cardParts[i].value == "" ||
						cardParts[i].value.length < 3 || 
						!cardParts[i].value.match(/[0-9]{4}/)
					){
						switch(i){
							case 0: number = "$first"; break;
							case 1: number = "$second"; break;
							case 2: number = "$third"; break;
							case 3: number = "$fourth"; break;
						}
						validationError(cardParts[i],"$error1 " + number + " $error2","validation",false);
					return false;
					}
				}
			}
			return true;			
		}
	}
});
JS;
		Requirements :: customScript($jsFunc, 'func_validateCreditCard');
		
		return "\$('$formID').validateCreditCard('$this->name');";
	}
	
	function validate($validator){
		// If the field is empty then don't return an invalidation message
		if(!trim(implode("", $this->value))) return true;
		
		$i=0;
		if($this->value) foreach($this->value as $part){
			if(!$part || !(strlen($part) == 4) || !ereg("([0-9]{4})",$part)){
				switch($i){
				        case 0: $number = _t('CreditCardField.FIRST', 'first'); break;
					case 1: $number = _t('CreditCardField.SECOND', 'second'); break;
					case 2: $number = _t('CreditCardField.THIRD', 'third'); break;
					case 3: $number = _t('CreditCardField.FOURTH', 'fourth'); break;
				}
				$validator->validationError(
					$this->name,
					sprintf(
						_t('Form.VALIDATIONCREDITNUMBER', "Please ensure you have entered the %s credit card number correctly."),
						$number
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
