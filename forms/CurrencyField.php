<?php
/**
 * Currency field.
 * 
 * @todo Add localization support, see http://open.silverstripe.com/ticket/2931 
 *
 * @package forms
 * @subpackage fields-formattedinput
 */
class CurrencyField extends TextField {
	/**
	 * allows the value to be set ( not including $ signs and number format...)
	 */
	function setValue($val) {
		$value = ($val) ? $val : 0.00;
		$this->value = '$' . number_format((double)preg_replace('/[^0-9.\-]/', '', $value), 2);
	}
	/**
	 * Overwrite the datavalue before saving to the db ;-)
	 */
	function dataValue() {
		if($this->value){
			return preg_replace('/[^0-9.\-]/','', $this->value);
		}else{
			return 0.00;
		}
	}
	/**
	 * Create a new class for this field
	 */
	function performReadonlyTransformation() {
		
		$field = new CurrencyField_Readonly($this->name, $this->title, $this->value);
		$field -> addExtraClass($this->extraClass());
		return $field;
		
		/*
		$this is-a object and cant be passed as_a string of the first parameter of formfield constructor.
		return new CurrencyField_Readonly($this);
		*/
	}
	
	/**
	 * @see http://regexlib.com/REDetails.aspx?regexp_id=126
	 */
	function jsValidation() {
		$formID = $this->form->FormName();
		$error = _t('CurrencyField.VALIDATIONJS', 'Please enter a valid currency.');
		$jsFunc =<<<JS
Behaviour.register({
	"#$formID": {
		validateCurrency: function(fieldName) {
			var el = _CURRENT_FORM.elements[fieldName];
			if(!el || !el.value) return true;
			
			var value = \$F(el);
			if(value.length > 0 && !value.match(/^\s*(-?\\\$?|\\\$-?)?(\d{1,3}(\,\d{3})*|(\d+))(\.\d{2})?\s*\$/)) {
				validationError(el,"$error","validation",false);
				return false;
			}
			return true;			
		}
	}
});
JS;

		Requirements::customScript($jsFunc, 'func_validateCurrency_' .$formID);

		return <<<JS
		if(\$('$formID')) \$('$formID').validateCurrency('$this->name');
JS;
	}

	function validate($validator) {
		if(!empty ($this->value) && !preg_match('/^\s*(\-?\$?|\$\-?)?(\d{1,3}(\,\d{3})*|(\d+))(\.\d{2})?\s*$/', $this->value)) {
			$validator->validationError($this->name, _t('Form.VALIDCURRENCY', "Please enter a valid currency."), "validation", false);
			return false;
		}
		return true;
	}
}

/**
 * Readonly version of a {@link CurrencyField}.
 * @package forms
 * @subpackage fields-formattedinput
 */
class CurrencyField_Readonly extends ReadonlyField{
	
	/**
	 * overloaded to display the correctly formated value for this datatype 
	 */
	function Field() {
		if($this->value){
			$val = $this->dontEscape ? ($this->reserveNL?Convert::raw2xml($this->value):$this->value) : Convert::raw2xml($this->value);
			$val = _t('CurrencyField.CURRENCYSYMBOL', '$') . number_format(preg_replace('/[^0-9.]/',"",$val), 2);
			
		}else {
		        $val = '<i>'._t('CurrencyField.CURRENCYSYMBOL', '$').'0.00</i>';
		}
		$valforInput = $this->value ? Convert::raw2att($val) : "";
		return "<span class=\"readonly ".$this->extraClass()."\" id=\"" . $this->id() . "\">$val</span><input type=\"hidden\" name=\"".$this->name."\" value=\"".$valforInput."\" />";
	}
	/**
	 * This already is a readonly field.
	 */
	function performReadonlyTransformation() {
		return clone $this;
	}
	
}

/**
 * Readonly version of a {@link CurrencyField}.
 * @package forms
 * @subpackage fields-formattedinput
 */
class CurrencyField_Disabled extends CurrencyField{
	
	protected $disabled = true;
	
	/**
	 * overloaded to display the correctly formated value for this datatype 
	 */
	function Field() {
		if($this->value){
			$val = $this->dontEscape ? ($this->reserveNL?Convert::raw2xml($this->value):$this->value) : Convert::raw2xml($this->value);
			$val = _t('CurrencyField.CURRENCYSYMBOL', '$') . number_format(preg_replace('/[^0-9.]/',"",$val), 2);
			
		}else {
		        $val = '<i>'._t('CurrencyField.CURRENCYSYMBOL', '$').'0.00</i>';
		}
		$valforInput = $this->value ? Convert::raw2att($val) : "";
		return "<input class=\"text\" type=\"text\" disabled=\"disabled\" name=\"".$this->name."\" value=\"".$valforInput."\" />";
	}
}

?>