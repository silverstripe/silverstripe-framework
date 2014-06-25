<?php

/**
 * Text input field with validation for numeric values. Supports validating
 * the numeric value as to the {@link i18n::get_locale()} value.
 * 
 * @package forms
 * @subpackage fields-formattedinput
 */
class NumericField extends TextField {

	public function Type() {
		return 'numeric text';
	}

	public function validate($validator) {
		if(!$this->value && !$validator->fieldIsRequired($this->name)) {
			return true;
		}
		
		require_once THIRDPARTY_PATH."/Zend/Locale/Format.php";

		$valid = Zend_Locale_Format::isNumber(
			trim($this->value), 
			array('locale' => i18n::get_locale())
		);

		if(!$valid) {
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
		
		return true;
	}
    
	/**
	 * displays the value in its current locality format
	 */
	public function setValue($val) {
                
                require_once THIRDPARTY_PATH."/Zend/Locale/Format.php";
                    
		if(is_numeric($val)){		
                    $locale = new Zend_Locale(i18n::get_locale());
                    $this->value = Zend_Locale_Format::toNumber($val, array('locale' => $locale));
                }else if(Zend_Locale_Format::isNumber(
                        trim($val), 
			array('locale' => i18n::get_locale())
                )){
                    $this->value = trim($val);
                }
		return $this;
	}
	
        /**
         * extracts the 
         * @return type
         */
	public function dataValue() {
		
		require_once THIRDPARTY_PATH."/Zend/Locale/Format.php";
                
                $locale = new Zend_Locale(i18n::get_locale());
                $number = Zend_Locale_Format::getNumber($this->value, array('locale' => $locale));
		return $number;
	}
}
