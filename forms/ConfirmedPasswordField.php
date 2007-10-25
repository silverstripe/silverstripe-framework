<?php
/**
 * Shows two password-fields, and checks for matching passwords.
 * 
 * TODO readonlytransformation
 */
class ConfirmedPasswordField extends FormField {
	
	public $minLength = 6;
	
	public $maxLength = 20;
	
	public $excludeChars = '\s';
	
	public $requireStrongPassword = false;
	
	public $canBeEmpty = false;
	
	function __construct($name, $title = null, $value = "", $form = null) {
		// we have labels for the subfields
		$title = false;
		
		// naming with underscores to prevent values from actually being saved somewhere
		$this->children = new FieldSet(
			new PasswordField("{$name}[_Password]", _t('Member.PASSWORD')),
			new PasswordField("{$name}[_ConfirmPassword]",_t('Member.CONFIRMPASSWORD', 'Confirm Password'))
		);
		
		parent::__construct($name, $title, $value, $form);
	}
	
	function Field() {
		$content = '';
		foreach($this->children as $field) {
			$content.= $field->FieldHolder();
		}
		
		return $content;
	}
	
	/**
	 * Can be empty is a flag that turns on/off empty field checking.
	 * For example, set this to false (the default) when creating a user account,
	 * and true 
	 */
	function setCanBeEmpty($value) {
		$this->canBeEmpty = (bool)$value;
	}
	
	function setRightTitle($title) {
		foreach($this->children as $field) {
			$field->setRightTitle($title);
		}
	}
	
	/**
	 * Value is sometimes an array, and sometimes a single value, so we need to handle both cases
	 */
	function setValue($value) {
		if(is_array($value)) {
			if($value['_Password'] || (!$value['_Password'] && !$this->canBeEmpty)) {
				$this->value = $value['_Password'];
			}
		} else {
			if($value || (!$value && !$this->canBeEmpty)) {
				$this->value = $value;
			}
		}
		
	}
	
	function jsValidation()
	{
		$formID = $this->form->FormName();
		
		$jsTests = "
			if(passEl.value != confEl.value) {
				validationError(confEl, \"Passwords have to match.\", \"error\");
				return false;
			}
		";
		
		if(!$this->canBeEmpty) {
			$jsTests .= "
				if(!passEl.value || !confEl.value) {
					validationError(confEl, \"Passwords can't be empty.\", \"error\");
					return false;
				}
			";
		}
		
		if(($this->minLength || $this->maxLength) && !$this->canBeEmpty) {
			if($this->minLength && $this->maxLength) {
				$limit = "{$this->minLength},{$this->maxLength}";
				$errorMsg = "Passwords must be {$this->minLength} to {$this->maxLength} characters long.";
			} elseif($this->minLength) {
				$limit = "{$this->minLength}";
				$errorMsg = "Passwords must be at least {$this->minLength} characters long.";
			} elseif($this->maxLength) {
				$limit = "0,{$this->maxLength}";
				$errorMsg = "Passwords must be at most {$this->maxLength} characters long.";
			}
			$limitRegex = '/^.{' . $limit . '}$/';
			$jsTests .= "
			if(!passEl.value.match({$limitRegex})) {
				validationError(confEl, \"{$errorMsg}\", \"error\");
				return false;
			}
			";
		}
		
		if($this->requireStrongPassword) {
			$jsTests .= "
				if(!passEl.value.match(/^(([a-zA-Z]+\d+)|(\d+[a-zA-Z]+))[a-zA-Z0-9]*$/)) {
					validationError(
						confEl, 
						\"Passwords must have at least one digit and one alphanumeric character.\", 
						\"error\"
					);
					return false;
				}
			";
		}
		
		$jsFunc =<<<JS
Behaviour.register({
	"#$formID": {
		validateConfirmedPassword: function(fieldName) {
			var passEl = _CURRENT_FORM.elements['Password[_Password]'];
			var confEl = _CURRENT_FORM.elements['Password[_ConfirmPassword]'];
			$jsTests
			return true;
		}
	}
});
JS;
		Requirements :: customScript($jsFunc, 'func_validateConfirmedPassword');
		
		//return "\$('$formID').validateConfirmedPassword('$this->name');";
		return <<<JS
if(typeof fromAnOnBlur != 'undefined'){
	if(fromAnOnBlur.name == '$this->name')
		$('$formID').validateConfirmedPassword('$this->name');
}else{
	$('$formID').validateConfirmedPassword('$this->name');
}
JS;
	}

	function validate() {
		$validator = $this->form->getValidator();
		$name = $this->name;
		$passwordField = $this->children->fieldByName($name.'[_Password]');
		$passwordConfirmField = $this->children->fieldByName($name.'[_ConfirmPassword]');
		$passwordField->setValue($_POST[$name]['_Password']);
		$passwordConfirmField->setValue($_POST[$name]['_ConfirmPassword']);
		// both password-fields should be the same
		if($passwordField->Value() != $passwordConfirmField->Value()) {
			$validator->validationError($name, _t('Form.VALIDATIONPASSWORDSDONTMATCH',"Passwords don't match"), "validation", false);
			return false;
		}

		if(!$this->canBeEmpty) {
			// both password-fields shouldn't be empty
			if(!$passwordField->Value() || !$passwordConfirmField->Value()) {
				$validator->validationError($name, _t('Form.VALIDATIONPASSWORDSNOTEMPTY', "Passwords can't be empty"), "validation", false);
				return false;
			}
		}
			
		// lengths
		if(($this->minLength || $this->maxLength) && !$this->canBeEmpty) {
			if($this->minLength && $this->maxLength) {
				$limit = "{$this->minLength},{$this->maxLength}";
				$errorMsg = "Passwords must be {$this->minLength} to {$this->maxLength} characters long.";
			} elseif($this->minLength) {
				$limit = "{$this->minLength}";
				$errorMsg = "Passwords must be at least {$this->minLength} characters long.";
			} elseif($this->maxLength) {
				$limit = "0,{$this->maxLength}";
				$errorMsg = "Passwords must be at most {$this->maxLength} characters long.";
			}
			$limitRegex = '/^.{' . $limit . '}$/';
			if(!preg_match($limitRegex,$passwordField->Value())) {
				$validator->validationError('Password', $errorMsg, 
					"validation", 
					false
				);
			}
		}
		
		if($this->requireStrongPassword) {
			if(!preg_match('/^(([a-zA-Z]+\d+)|(\d+[a-zA-Z]+))[a-zA-Z0-9]*$/',$passwordField->Value())) {
				$validator->validationError(
					'Password', 
					_t('Form.VALIDATIONSTRONGPASSWORD', "Passwords must have at least one digit and one alphanumeric character."), 
					"validation", 
					false
				);
				return false;
			}
		}
		return true;
	}
}