<?php

/**
 * @package forms
 * @subpackage validators
 */

/**
 * This validation class handles all form and custom form validation through
 * the use of Required fields.
 * 
 * Relies on javascript for client-side validation, and marking fields after serverside validation.
 * 
 * Acts as a visitor to individual form fields.
 * 
 * @todo Automatically mark fields after serverside validation and replace the form through
 * FormResponse if the request was made by ajax.
 * 
 * @package forms
 * @subpackage validators
 */ 
abstract class Validator extends Object {
	protected $form;
	protected $errors;

	public function __construct() {
		Requirements::javascript('sapphire/javascript/Validator.js');
		
		parent::__construct();
	}
	
	function setForm($form) {
		$this->form = $form;
	}
	
	function validate(){
		$this->errors = null;
		$this->php($this->form->getData());
			
		return $this->errors;
	}
	
	/**
	 * Callback to register an error on a field (Called from implementations of {@link FormField::validate})
	 * 
	 * @param $fieldName name of the field
	 * @param $message error message to display
	 * @param $messageType optional parameter, gets loaded into the HTML class attribute in the rendered output
	 */
	function validationError($fieldName,$message,$messageType=''){
		$this->errors[] = array(
			'fieldName' => $fieldName,
			'message' => $message,
			'messageType' => $messageType,
		);
	}
	
	function showError(){
		debug::show($this->errors);
	}
	
	function getCombinedError(){
		if($this->errors) {
			foreach($this->errors as $error){
				$ret['message'] .= $error['message']."<br />";
				$ret['messageType'] .= $error['messageType']."<br />";
			}
		
			return $ret;
		}
	}
	function getError(){
		return $this->errors;
	}
	
	function requireField($fieldName, $data) {
		if(!$data[$fieldName]) $this->validationError($fieldName, "$fieldName is required", "required");
	}
	
	function includeJavascriptValidation() {
		Requirements::javascript("jsparty/prototype.js");
		Requirements::javascript("jsparty/behaviour.js");
		Requirements::javascript("jsparty/prototype_improvements.js");
		Requirements::javascript("sapphire/javascript/Validator.js");

		$code = $this->javascript();
		$formID = $this->form->FormName();
		$js = <<<JS
Behaviour.register({
	'#$formID': {
		validate : function(fromAnOnBlur) {
			initialiseForm(this, fromAnOnBlur);
			$code
			
			var error = hasHadFormError();
			if(!error && fromAnOnBlur) clearErrorMessage(fromAnOnBlur);
			
			return !error;
		},
		onsubmit : function() {
			if(typeof this.bypassValidation == 'undefined' || !this.bypassValidation) return this.validate();
		}
	},
	'#$formID input' : {
		initialise: function() {
			if(!this.old_onblur) this.old_onblur = function() { return true; } 
			if(!this.old_onfocus) this.old_onfocus = function() { return true; } 
		},
		onblur : function() {
			if(this.old_onblur()) {
				return $('$formID').validate(this);
			}
		}
	},
	'#$formID select' : {
		initialise: function() {
			if(!this.old_onblur) this.old_onblur = function() { return true; } 
		},
		onblur : function() {
			if(this.old_onblur()) {
				return $('$formID').validate(this); 
			}
		}
	}
});
// TODO Performance-issue: Behaviour is possibly applied twice
Behaviour.apply('#$formID');	
JS;

	Requirements::customScript($js);
	}
	
	/**
	 * Returns true if the named field is "required".
	 * Used by FormField to return a value for FormField::Required(), to do things like show *s on the form template.
	 * By default, it always returns false.
	 */
	function fieldIsRequired($fieldName) {
		return false;
	}
	
	abstract function javascript();
	
	abstract function php($data);
}
?>