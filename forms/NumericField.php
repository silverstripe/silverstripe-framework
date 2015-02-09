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
		
		if(!is_numeric($this->value)) {
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

    
	public function setValue($value, $context = null) {
        // select the appropriate setter
        // @todo: deprecate ignorant getters and setters and remove this terrible hack
        if ($context instanceof DataObject) {
            return $this->setDataValue($value);
        } else {
            $this->setViewValue($value);
            return $this;
        }
    }

	public function Value() {
        // use the appropriate getter
        return $this->getViewValue();
	}

	public function dataValue() {
        // use the appropriate getter
        return $this->getDataValue();
	}

    /**
     * @param $value (mixed) data representation of the value, e.g. (float)1.23
    **/
    public function setDataValue($value)
    {
        return parent::setValue($value);
    }

    /**
     * @param $value (mixed) view representation of the value, e.g. (string)"1.200,00"
    **/
    public function setViewValue($value)
    {
        $this->value = $value || $value === 0 ? Zend_Locale_Format::getNumber(
            trim($value),
            array('locale' => i18n::get_locale())
        ) : '';
    }

    /**
     * @return (mixed) numeric data representation of the value, e.g. (float)1.23
    **/
    public function getDataValue()
    {
		return (is_numeric($this->value)) ? $this->value : 0;
    }

    /**
     * @return (string) view representation of the value, e.g. "1.200,00"
    **/
    public function getViewValue()
    {
		return Zend_Locale_Format::toNumber(
			trim($this->value), 
			array('locale' => i18n::get_locale())
		);
    }

	/**
	 * Returns a readonly version of this field
	 */
	public function performReadonlyTransformation() {
		$field = new NumericField_Readonly($this->name, $this->title, $this->value);
		$field->setForm($this->form);
		return $field;
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
