<?php
/**
 * Two masked input fields, checks for matching passwords.
 * Optionally hides the fields by default and shows
 * a link to toggle their visibility.
 * 
 * @package forms
 * @subpackage fields-formattedinput
 */
class ConfirmedPasswordField extends FormField {
	
	/**
	 * Minimum character length of the password.
	 *
	 * @var int
	 */
	public $minLength = null;
	
	/**
	 * Maximum character length of the password.
	 *
	 * @var int
	 */
	public $maxLength = null;
	
	/**
	 * Enforces at least one digit and one alphanumeric
	 * character (in addition to {$minLength} and {$maxLength}
	 *
	 * @var boolean
	 */
	public $requireStrongPassword = false;
	
	/**
	 * Allow empty fields in serverside validation
	 *
	 * @var boolean
	 */
	public $canBeEmpty = false;
	
	/**
	 * If set to TRUE, the "password" and "confirm password"
	 * formfields will be hidden via CSS and JavaScript by default,
	 * and triggered by a link. An additional hidden field
	 * determines if showing the fields has been triggered,
	 * and just validates/saves the input in this case.
	 * This behaviour works unobtrusively, without JavaScript enabled
	 * the fields show, validate and save by default.
	 * 
	 * @param boolean $showOnClick
	 */
	protected $showOnClick = false;
	
	/**
	 * Title for the link that triggers
	 * the visibility of password fields.
	 *
	 * @var string
	 */
	public $showOnClickTitle;
	
	/**
	 * @param string $name
	 * @param string $title
	 * @param mixed $value
	 * @param Form $form
	 * @param boolean $showOnClick
	 * @param string $titleConfirmField Alternate title (not localizeable)
	 */
	function __construct($name, $title = null, $value = "", $form = null, $showOnClick = false, $titleConfirmField = null) {
		// naming with underscores to prevent values from actually being saved somewhere
		$this->children = new FieldList(
			new PasswordField(
				"{$name}[_Password]", 
				(isset($title)) ? $title : _t('Member.PASSWORD', 'Password')
			),
			new PasswordField(
				"{$name}[_ConfirmPassword]",
				(isset($titleConfirmField)) ? $titleConfirmField : _t('Member.CONFIRMPASSWORD', 'Confirm Password')
			)
		);
		
		// has to be called in constructor because Field() isn't triggered upon saving the instance
		if($showOnClick) {
			$this->children->push(new HiddenField("{$name}[_PasswordFieldVisible]"));
		}
		$this->showOnClick = $showOnClick;
		
		// we have labels for the subfields
		$title = false;
		
		parent::__construct($name, $title, null, $form);
		$this->setValue($value);
	}
	
	function Field($properties = array()) {
		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery/jquery.js');
		Requirements::javascript(FRAMEWORK_DIR . '/javascript/ConfirmedPasswordField.js');
		Requirements::css(FRAMEWORK_DIR . '/css/ConfirmedPasswordField.css');
		
		$content = '';
		
		if($this->showOnClick) {
			if($this->showOnClickTitle) {
				$title = $this->showOnClickTitle;
			} else {
				$title = _t(
					'ConfirmedPasswordField.SHOWONCLICKTITLE', 
					'Change Password', 
					
					'Label of the link which triggers display of the "change password" formfields'
				);
			}
			
			$content .= "<div class=\"showOnClick\">\n";
			$content .= "<a href=\"#\">{$title}</a>\n";
			$content .= "<div class=\"showOnClickContainer\">";
		}

		foreach($this->children as $field) {
			$field->setDisabled($this->isDisabled()); 
			$field->setReadonly($this->isReadonly());
			$content .= $field->FieldHolder();
		}

		if($this->showOnClick) {
			$content .= "</div>\n";
			$content .= "</div>\n";
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
		return $this;
	}
	
	/**
	 * The title on the link which triggers display of the
	 * "password" and "confirm password" formfields.
	 * Only used if {@link setShowOnClick()} is set to TRUE.
	 * 
	 * @param $title
	 */
	public function setShowOnClickTitle($title) {
		$this->showOnClickTitle = $title;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getShowOnClickTitle() {
		return $this->showOnClickTitle;
	}
	
	function setRightTitle($title) {
		foreach($this->children as $field) {
			$field->setRightTitle($title);
		}
		return $this;
	}
	
	/**
	 * @param array: 2 entrie array with the customised title for each of the 2 children.
	 */
	function setChildrenTitles($titles) {
		if(is_array($titles)&&count($titles)==2){
			foreach($this->children as $field) {
				if(isset($titles[0])){
					$field->setTitle($titles[0]);
					array_shift($titles);		
				}
			}
		}
		return $this;
	}
	
	/**
	 * Value is sometimes an array, and sometimes a single value, so we need to handle both cases
	 */
	function setValue($value) {
		if(is_array($value)) {
			if($value['_Password'] || (!$value['_Password'] && !$this->canBeEmpty)) {
				$this->value = $value['_Password'];
			}
			if($this->showOnClick && isset($value['_PasswordFieldVisible'])){
				$this->children->fieldByName($this->getName() . '[_PasswordFieldVisible]')->setValue($value['_PasswordFieldVisible']);
			}
		} else {
			if($value || (!$value && $this->canBeEmpty)) {
				$this->value = $value;
			}
		}
		$this->children->fieldByName($this->getName() . '[_Password]')->setValue($this->value);
		$this->children->fieldByName($this->getName() . '[_ConfirmPassword]')->setValue($this->value);

		return $this;
	}

	/**
	 * Determines if the field was actually
	 * shown on the clientside - if not,
	 * we don't validate or save it.
	 * 
	 * @return bool
	 */
	function isSaveable() {
		$isVisible = $this->children->fieldByName($this->getName() . '[_PasswordFieldVisible]');
		return (!$this->showOnClick || ($this->showOnClick && $isVisible && $isVisible->Value()));
	}
	
	function validate($validator) {
		$name = $this->name;
		
		// if field isn't visible, don't validate
		if(!$this->isSaveable()) return true; 
		
		$passwordField = $this->children->fieldByName($name.'[_Password]');
		$passwordConfirmField = $this->children->fieldByName($name.'[_ConfirmPassword]');
		$passwordField->setValue($_POST[$name]['_Password']);
		$passwordConfirmField->setValue($_POST[$name]['_ConfirmPassword']);
		
		$value = $passwordField->Value();
		
		// both password-fields should be the same
		if($value != $passwordConfirmField->Value()) {
			$validator->validationError($name, _t('Form.VALIDATIONPASSWORDSDONTMATCH',"Passwords don't match"), "validation", false);
			return false;
		}

		if(!$this->canBeEmpty) {
			// both password-fields shouldn't be empty
			if(!$value || !$passwordConfirmField->Value()) {
				$validator->validationError($name, _t('Form.VALIDATIONPASSWORDSNOTEMPTY', "Passwords can't be empty"), "validation", false);
				return false;
			}
		}
			
		// lengths
		if(($this->minLength || $this->maxLength)) {
			if($this->minLength && $this->maxLength) {
				$limit = "{{$this->minLength},{$this->maxLength}}";
				$errorMsg = _t(
					'ConfirmedPasswordField.BETWEEN', 
					'Passwords must be {min} to {max} characters long.', 
					array('min' => $this->minLength, 'max' => $this->maxLength)
				);
			} elseif($this->minLength) {
				$limit = "{{$this->minLength}}.*";
				$errorMsg = _t(
					'ConfirmedPasswordField.ATLEAST', 
					'Passwords must be at least {min} characters long.', 
					array('min' => $this->minLength)
				);
			} elseif($this->maxLength) {
				$limit = "{0,{$this->maxLength}}";
				$errorMsg = _t(
					'ConfirmedPasswordField.MAXIMUM', 
					'Passwords must be at most {max} characters long.', 
					array('max' => $this->maxLength)
				);
			}
			$limitRegex = '/^.' . $limit . '$/';
			if(!empty($value) && !preg_match($limitRegex,$value)) {
				$validator->validationError('Password', $errorMsg, 
					"validation", 
					false
				);
			}
		}
		
		if($this->requireStrongPassword) {
			if(!preg_match('/^(([a-zA-Z]+\d+)|(\d+[a-zA-Z]+))[a-zA-Z0-9]*$/',$value)) {
				$validator->validationError(
					'Password', 
					_t('Form.VALIDATIONSTRONGPASSWORD', "Passwords must have at least one digit and one alphanumeric character"), 
					"validation", 
					false
				);
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Only save if field was shown on the client,
	 * and is not empty.
	 *
	 * @param DataObject $record
	 * @return bool
	 */
	function saveInto(DataObjectInterface $record) {
		if(!$this->isSaveable()) return false;
		
		if(!($this->canBeEmpty && !$this->value)) {
			parent::saveInto($record);
		}
	}
	
	/**
	 * Makes a pretty readonly field with some stars in it
	 */
	function performReadonlyTransformation() {
		$stars = '*****';

		$field = new ReadonlyField($this->name, $this->title ? $this->title : _t('Member.PASSWORD'), $stars);
		$field->setForm($this->form);
		return $field;
	}
}
