<?php

/**
 * @package forms
 * @subpackage fields-formattedinput
 */

/**
 * Text field that automatically checks that the value entered is unique for the given
 * set of fields in a given set of tables
 * @package forms
 * @subpackage fields-formattedinput
 */
class AjaxUniqueTextField extends TextField {
	
	protected $restrictedField;
	protected $restrictedTable;
	// protected $restrictedMessage;
	protected $validateURL;
	
	protected $restrictedRegex;
	
	function __construct($name, $title, $restrictedField, $restrictedTable, $value = "", $maxLength = null, $validationURL = null, $restrictedRegex = null ){
		$this->maxLength = $maxLength;
		
		$this->restrictedField = $restrictedField;
		
		$this->restrictedTable = $restrictedTable;
		
		$this->validateURL = $validationURL;
		
		$this->restrictedRegex = $restrictedRegex;
		
		parent::__construct($name, $title, $value);	
	}
	 
	function Field() {
		Requirements::javascript("sapphire/javascript/UniqueFields.js");
		
		$this->jsValidation();
		
		$url = Convert::raw2att( $this->validateURL );
		
		if( $this->restrictedRegex )
			$restrict = "<input type=\"hidden\" class=\"hidden\" name=\"{$this->name}Restricted\" id=\"" . $this->id() . "RestrictedRegex\" value=\"{$this->restrictedRegex}\" />";
		
		if($this->maxLength) {
			return "<input class=\"".$this->class."\" text\" type=\"text\" id=\"" . $this->id() . "\" name=\"{$this->name}\" value=\"" . $this->attrValue() . "\" maxlength=\"$this->maxLength\" />$restrict";
		} else {
			return "<input class=\"".$this->class."\" text\" type=\"text\" id=\"" . $this->id() . "\" name=\"{$this->name}\" value=\"" . $this->attrValue() . "\" />$restrict"; 
		}
	}

	function jsValidation() {
		$formID = $this->form->FormName();
		$id = $this->id();
		$url = Director::absoluteBaseURL() . $this->validateURL;

		if($this->restrictedRegex) {
			$jsCheckFunc = <<<JS
Element.removeClassName(this, 'invalid');
var match = this.value.match(/{$this->restrictedRegex}/);
if(match) {
	Element.addClassName(this, 'invalid');
	return false;
}

return true;	
JS;
		} else {
			$jsCheckFunc = "return true;";
		}

		$jsFunc = <<<JS
Behaviour.register({
	'#$id' : {
		onkeyup: function() {
			if(this.checkValid()) {
				new Ajax.Request('{$url}?ajax=1&{$this->name}=' + encodeURIComponent(this.value), { 
					method: 'get',
					onSuccess: function(response) {
						console.debug(this);
						if(response.responseText == 'ok')
							Element.removeClassName(this, 'inuse');
						else {
							Element.addClassName(this, 'inuse');	
						}
					}.bind(this),
					onFailure: function(response) {
					
					}	
				});
			}
		},
		
		checkValid: function() {
			$jsCheckFunc
		}
	} 
});
JS;
		Requirements::customScript($jsFunc, 'func_validateAjaxUniqueTextField');

		//return "\$('$formID').validateCurrency('$this->name');";

	}

	function validate( $validator ) {
		
		$result = DB::query(sprintf(
			"SELECT COUNT(*) FROM `%s` WHERE `%s` = '%s'",
			$this->restrictedTable,
			$this->restrictedField,
			Convert::raw2sql($this->value)
		))->value();

		if( $result && ( $result > 0 ) ) {
			$validator->validationError( $this->name, _t('Form.VALIDATIONNOTUNIQUE', "The value entered is not unique") );
			return false;
		}

		return true; 
	}
}
?>