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
	
	/**
	 * @var Form $form
	 */
	protected $form;
	
	/**
	 * @var array $errors
	 */
	protected $errors;
	
	/**
	 * Static for default value of $this->javascriptValidationHandler.
	 * Set with Validator::set_javascript_validation_handler();
	 * @var string
	 */
	protected static $javascript_validation_handler = "prototype";
	
	/**
	 * Handler for javascript validation.  Can be "prototype" or "none".
	 * @var string
	 */
	protected $javascriptValidationHandler = null;
	
	/**
	 * Call this function to set the javascript validation handler for all valdiation on your site.
	 * This could be called from _config.php to set site-wide javascript validation, or from ContentController::init()
	 * to affect only the front-end site.
	 * Use instance method {@link setJavascriptValidationHandler()} to
	 * only set handler for a specific form instance.
	 *
	 * @param $handler A string representing the handler to use: 'prototype' or 'none'.
	 * @todo Add 'jquery' as a handler option.
	 */
	public static function set_javascript_validation_handler($handler) {
		if($handler == 'prototype' || $handler == 'none') {
			self::$javascript_validation_handler = $handler;
		} else {
			user_error("Validator::setJavascriptValidationHandler() passed bad handler '$handler'", E_USER_WARNING);
		}
	}
	
	/**
	 * Returns global validation handler used for all forms by default,
	 * unless overwritten by {@link setJavascriptValidationHandler()}.
	 * 
	 * @return string
	 */
	public static function get_javascript_validator_handler() {
		return self::$javascript_validation_handler;
	}

	/**
	 * Set JavaScript validation for this validator.
	 * Use static method {@link set_javascript_validation_handler()}
	 * to set handlers globally.
	 * 
	 * @param string $handler
	 */
	public function setJavascriptValidationHandler($handler) {
		if($handler == 'prototype' || $handler == 'none') {
			$this->javascriptValidationHandler = $handler; 
		} else {
			user_error("Validator::setJavascriptValidationHandler() passed bad handler '$handler'", E_USER_WARNING);
		}
	}

	/**
	 * Gets the current javascript validation handler for this form.
	 * If not set, falls back to the global static {@link self::$javascript_validation_handler}.
	 * 
	 * @return string
	 */
	public function getJavascriptValidationHandler() {
		return ($this->javascriptValidationHandler) ? $this->javascriptValidationHandler : self::$javascript_validation_handler;
	}
	
	/**
	 * @param Form $form
	 */
	function setForm($form) {
		$this->form = $form;
	}
	
	/**
	 * @return array Errors (if any)
	 */
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
	 * @param $messageType optional parameter, gets loaded into the HTML class attribute in the rendered output. See {@link getErrors()} for details.
	 */
	function validationError($fieldName, $message, $messageType='') {
		$this->errors[] = array(
			'fieldName' => $fieldName,
			'message' => $message,
			'messageType' => $messageType,
		);
	}
	
	/**
	 * @deprecated 2.4 Use Validator->getErrors() and custom code
	 */
	function showError() {
		Debug::show($this->errors);
	}
	
	/**
	 * @deprecated 2.4 Use custom code
	 */
	function getCombinedError(){
		if($this->errors) {
			foreach($this->errors as $error){
				$ret['message'] .= $error['message']."<br />";
				$ret['messageType'] .= $error['messageType']."<br />";
			}
		
			return $ret;
		}
	}
	
	/**
	 * @deprecated 2.4 Use getErrors()
	 */
	function getError(){
		return $this->getErrors();
	}
	
	/**
	 * Returns all errors found by a previous call to {@link validate()}.
	 * The array contains the following keys for each error:
	 * - 'fieldName': the name of the FormField instance
	 * - 'message': Validation message (optionally localized)
	 * - 'messageType': Arbitrary type of the message which is rendered as a CSS class in the FormField template,
	 *   e.g. <span class="message (type)">. Usually "bad|message|validation|required", which renders differently
	 *   if sapphire/css/Form.css is included.
	 * 
	 * @return array
	 */
	function getErrors() {
		return $this->errors;
	}
	
	function requireField($fieldName, $data) {
		if(is_array($data[$fieldName]) && count($data[$fieldName])) {
			foreach($data[$fieldName] as $componentkey => $componentVal){
				if(!strlen($componentVal)) $this->validationError($fieldName, "$fieldName $componentkey is required.", "required");
			}
			
		}else if(!strlen($data[$fieldName])) $this->validationError($fieldName, "$fieldName is required.", "required");
	}
	
	function includeJavascriptValidation() {
		if($this->getJavascriptValidationHandler() == 'prototype') {
			Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/prototype/prototype.js");
			Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/behaviour/behaviour.js");
			Requirements::javascript(SAPPHIRE_DIR . "/javascript/prototype_improvements.js");
			Requirements::add_i18n_javascript(SAPPHIRE_DIR . '/javascript/lang');
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
			if(error && !fromAnOnBlur) focusOnFirstErroredField();
			
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
	'#$formID textarea' : {
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
