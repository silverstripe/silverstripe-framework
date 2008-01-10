<?php

/**
 * @package forms
 * @subpackage fields-datetime
 */

/**
 * Date field.
 * Default Value represented in the format
 * @package forms
 * @subpackage fields-datetime
 */
class DateField extends TextField {
	
	function setValue($val) {
		if($val && preg_match('/^([\d]{2,4})-([\d]{1,2})-([\d]{1,2})/', $val)) {
			$this->value = preg_replace('/^([\d]{2,4})-([\d]{1,2})-([\d]{1,2})/','\\3/\\2/\\1', $val);
		} else {
			$this->value = $val;
		}
	}
	
	function dataValue() {
		if(is_array($this->value)) {
			return $this->value['Year'] . '-' . $this->value['Month'] . '-' . $this->value['Day'];
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
		return $field;
	}
	
	function jsValidation($formID = null)
	{
		if(!$formID)$formID = $this->form->FormName(); 
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
		Requirements :: customScript($jsFunc, 'func_validateDate');
		
//		return "\$('$formID').validateDate('$this->name');";
		return <<<JS
if(typeof fromAnOnBlur != 'undefined'){
	if(fromAnOnBlur.name == '$this->name')
		$('$formID').validateDate('$this->name');
}else{
	$('$formID').validateDate('$this->name');
}
JS;
	}

	function validate($validator)
	{
		if(!empty ($this->value) && !preg_match('/^[0-9]{1,2}\/[0-9]{1,2}\/[0-90-9]{2,4}$/', $this->value))
		{
			$validator->validationError(
				$this->name, 
				_t('DateField.VALIDDATEFORMAT', "Please enter a valid  date format (DD/MM/YYYY)."), 
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
	
	function setValue($val) {
		if($val && $val != "0000-00-00") $this->value = date('d/m/Y', strtotime($val));
		else $this->value = '('._t('DateField.NODATESET', 'No date set').')';
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
		
		return "<span class=\"readonly\" id=\"" . $this->id() . "\">$val</span>";
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
}
?>