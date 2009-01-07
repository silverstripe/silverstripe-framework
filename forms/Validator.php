<?php
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
	
	/**
	 * Static for default value of $this->javascriptValidationHandler.
	 * Set with Validator::set_javascript_validation_handler();
	 */
	protected static $javascript_validation_handler = null;
	
	/**
	 * Handler for javascript validation.  Can be "prototype" or "none"
	 */
	protected $javascriptValidationHandler = "prototype";
	
	/**
	 * Call this function to set the javascript validation handler for all valdiation on your site.
	 * This could be called from _config.php to set site-wide javascript validation, or from ContentController::init()
	 * to affect only the front-end site.
	 *
	 * @param $handler A string representing the handler to use: 'prototype' or 'none'.
	 * @todo Add 'jquery' as a handler option.
	 */
	public function set_javascript_validation_handler($handler = 'prototype') {
		self::$javascript_validation_handler = $handler;
	}

	/**
	 * Disable JavaScript validation for this validator
	 */
	public function setJavascriptValidationHandler($handler) {
		if($handler == 'prototype' || $handler == 'none') {
			$this->javascriptValidationHandler = $handler;
		} else {
			user_error("Validator::setJavascriptValidationHandler() passed bad handler '$handler'", E_USER_WARNING);
		}
	}
	
	public function __construct() {
		if(self::$javascript_validation_handler) $this->setJavascriptValidationHandler(self::$javascript_validation_handler);
		
		if($this->javascriptValidationHandler && $this->javascriptValidationHandler != 'none') {
			Requirements::javascript(SAPPHIRE_DIR . '/javascript/Validator.js');
		}
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
		if($this->javascriptValidationHandler == 'prototype') {
			Requirements::javascript(THIRDPARTY_DIR . "/prototype.js");
			Requirements::javascript(THIRDPARTY_DIR . "/behaviour.js");
			Requirements::javascript(THIRDPARTY_DIR . "/prototype_improvements.js");
			Requirements::javascript(SAPPHIRE_DIR . "/javascript/i18n.js");
			Requirements::javascript(SAPPHIRE_DIR . "/javascript/Validator.js");
		
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
				// Don't perform instant validation for CalendarDateField fields; it creates usability wierdness.
				if(this.parentNode.className.indexOf('calendardate') == -1 || this.value) {
					return $('$formID').validate(this);
				} else {
					return true;
				}
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
JS;

			Requirements::customScript($js);
			// HACK Notify the form that the validators client-side validation code has already been included
			if($this->form) $this->form->jsValidationIncluded = true;
		}
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