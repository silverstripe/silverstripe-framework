<?php
/**
 * Date field.
 * Default Value represented in the format
 */
class DateField extends TextField {
	function setValue($val) {
		if(preg_match('/^([\d]{1,2})\/([\d]{1,2})\/([\d]{2,4})/', $val, $parts)) {
			$val = "$parts[3]-$parts[2]-$parts[1]";
		}
		if($val) $this->value = date('d/m/Y', strtotime($val));
		else $this->value = null;
	}
	function dataValue() {
		return preg_replace('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-90-9]{2,4})/', '\\3-\\2-\\1', $this->value);
	}
	function performReadonlyTransformation() {
		$field = new DateField_Disabled($this->name, $this->title, $this->value);
		$field->setForm($this->form);
		return $field;
	}
	
	function jsValidation()
	{
		$formID = $this->form->FormName();
		
		$jsFunc =<<<JS
Behaviour.register({
	"#$formID": {
		validateDate: function(fieldName) {
			var el = _CURRENT_FORM.elements[fieldName];
			var value = \$F(el);
			
			if(value && value.length > 0 && !value.match(/^[0-9]{1,2}\/[0-9]{1,2}\/[0-90-9]{2,4}\$/)) {
				validationError(el,"Please enter a valid date format (DD/MM/YYYY).","validation",false);
				return false;
			}
			return true;
		}
	}
});
JS;
		Requirements :: customScript($jsFunc, 'func_validateDate');
		
		return "\$('$formID').validateDate('$this->name');";
	}

	function validate()
	{
		if(!empty ($this->value) && !preg_match('/^[0-9]{1,2}\/[0-9]{1,2}\/[0-90-9]{2,4}$/', $this->value))
		{
			$validator->validationError($this->name, "Please enter a valid  date format (DD/MM/YYYY).", "validation", false);
			return false;
		}
		return true;
	}
}

/**
 * Allows dates to be represented in a form, by
 * showing in a user friendly format, eg, dd/mm/yyyy.
 */
class DateField_Disabled extends DateField {
	
	function setValue($val) {
		if($val && $val != "0000-00-00") $this->value = date('d/m/Y', strtotime($val));
		else $this->value = "(No date set)";
	}
	
	function Field() {
		if($this->value) {
			$df = new Date($this->name);
			$df->setValue($this->dataValue());
			
			if(date('Y-m-d', time()) == $this->dataValue()) {
				$val = Convert::raw2xml($this->value . ' (today)');
			} else {
				$val = Convert::raw2xml($this->value . ', ' . $df->Ago());
			}
		} else {
			$val = '<i>(not set)</i>';
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