<?php
/**
 * Date field.
 * Default Value represented in the format
 * @package forms
 * @subpackage fields-datetime
 */
class DateField extends TextField {
	
	function setValue($val) {
		if(is_string($val) && preg_match('/^([\d]{2,4})-([\d]{1,2})-([\d]{1,2})/', $val)) {
			$this->value = preg_replace('/^([\d]{2,4})-([\d]{1,2})-([\d]{1,2})/','\\3/\\2/\\1', $val);
		} else {
			$this->value = $val;
		}
	}
	
	function dataValue() {
		if(is_array($this->value)) {
			if(isset($this->value['Year']) && isset($this->value['Month']) && isset($this->value['Day'])) {
				return $this->value['Year'] . '-' . $this->value['Month'] . '-' . $this->value['Day'];
			} else {
				user_error("Bad DateField value " . var_export($this->value,true), E_USER_WARNING);
			}
		} elseif(preg_match('/^([\d]{1,2})\/([\d]{1,2})\/([\d]{2,4})/', $this->value, $parts)) {
			return "$parts[3]-$parts[2]-$parts[1]";
		} elseif(!empty($this->value)) {
			return date('Y-m-d', strtotime($this->value));
		} else {
			return null;
		}
	}
	
	function performReadonlyTransformation() {
		$field = new DateField_Disabled($this->name, $this->title, $this->value);
		$field->setForm($this->form);
		$field->readonly = true;
		return $field;
	}
	
	function jsValidation() {
		$formID = $this->form->FormName(); 
		$error = _t('DateField.VALIDATIONJS', 'Please enter a valid date format (DD/MM/YYYY).');
		$jsFunc =<<<JS
Behaviour.register({
	"#$formID": {
		validateDate: function(fieldName) {
			var el = _CURRENT_FORM.elements[fieldName];
			var value = \$F(el);
			
			if(value && value.length > 0 && !value.match(/^[0-9]{1,2}\/[0-9]{1,2}\/[0-90-9]{2,4}\$/)) {
				validationError(el,"$error","validation",false);
				return false;
			}
			return true;
		}
	}
});
JS;
		Requirements :: customScript($jsFunc, 'func_validateDate_'.$formID);
		
//		return "\$('$formID').validateDate('$this->name');";
		return <<<JS
if(\$('$formID')){
	if(typeof fromAnOnBlur != 'undefined'){
		if(fromAnOnBlur.name == '$this->name')
			\$('$formID').validateDate('$this->name');
	}else{
		\$('$formID').validateDate('$this->name');
	}
}
JS;
	}

	function validate($validator)
	{
		if(!empty ($this->value) && !preg_match('/^[0-9]{1,2}\/[0-9]{1,2}\/[0-90-9]{2,4}$/', $this->value))
		{
			$validator->validationError(
				$this->name, 
				_t('DateField.VALIDDATEFORMAT', "Please enter a valid date format (DD/MM/YYYY)."), 
				"validation", 
				false
			);
			return false;
		}
		return true;
	}
}

/**
 * Disabled version of {@link DateField}.
 * Allows dates to be represented in a form, by showing in a user friendly format, eg, dd/mm/yyyy.
 * @package forms
 * @subpackage fields-datetime
 */
class DateField_Disabled extends DateField {
	
	protected $disabled = true;
	
	function setValue($val) {
		if(is_string($val) && preg_match('/^([\d]{2,4})-([\d]{1,2})-([\d]{1,2})/', $val)) {
			$this->value = preg_replace('/^([\d]{2,4})-([\d]{1,2})-([\d]{1,2})/','\\3/\\2/\\1', $val);
		} else {
			$this->value = $val;
		}
	}
	
	function Field() {
		if($this->value) {
			$df = new Date($this->name);
			$df->setValue($this->dataValue());
			
			if(date('Y-m-d', time()) == $this->dataValue()) {
				$val = Convert::raw2xml($this->value . ' ('._t('DateField.TODAY','today').')');
			} else {
				$val = Convert::raw2xml($this->value . ', ' . $df->Ago());
			}
		} else {
			$val = '<i>('._t('DateField.NOTSET', 'not set').')</i>';
		}
		
		return "<span class=\"readonly\" id=\"" . $this->id() . "\">$val</span>
				<input type=\"hidden\" value=\"{$this->value}\" name=\"$this->name\" />";
	}
	
	function Type() { 
		return "date_disabled readonly";
	}
	
	function jsValidation() {
		return null;
	}

	function php() {
		return true;
	}
	
	function validate($validator) {
		return true;	
	}
}
?>