<?php

/**
 * Two masked input fields, checks for matching passwords.
 *
 * Optionally hides the fields by default and shows a link to toggle their
 * visibility.
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
	 * If set to TRUE, the "password" and "confirm password" form fields will
	 * be hidden via CSS and JavaScript by default, and triggered by a link.
	 *
	 * An additional hidden field determines if showing the fields has been
	 * triggered and just validates/saves the input in this case.
	 *
	 * This behaviour works unobtrusively, without JavaScript enabled
	 * the fields show, validate and save by default.
	 *
	 * @param boolean $showOnClick
	 */
	protected $showOnClick = false;

	/**
	 * Check if the existing password should be entered first
	 *
	 * @var bool
	 */
	protected $requireExistingPassword = false;


	/**
	 * A place to temporarily store the confirm password value
	 *
	 * @var string
	 */
	protected $confirmValue;

	/**
	 * Store value of "Current Password" field
	 *
	 * @var string
	 */
	protected $currentPasswordValue;

	/**
	 * Title for the link that triggers the visibility of password fields.
	 *
	 * @var string
	 */
	public $showOnClickTitle;

	/**
	 * Child fields (_Password, _ConfirmPassword)
	 *
	 * @var FieldList
	 */
	public $children;

	/**
	 * @param string $name
	 * @param string $title
	 * @param mixed $value
	 * @param Form $form
	 * @param boolean $showOnClick
	 * @param string $titleConfirmField Alternate title (not localizeable)
	 */
	public function __construct($name, $title = null, $value = "", $form = null, $showOnClick = false,
			$titleConfirmField = null) {

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

		// disable auto complete
		foreach($this->children as $child) {
			/** @var FormField $child */
			$child->setAttribute('autocomplete', 'off');
		}

		$this->showOnClick = $showOnClick;

		// we have labels for the subfields
		$title = false;

		parent::__construct($name, $title);
		$this->setValue($value);
	}

	/**
	 * @param array $properties
	 *
	 * @return HTMLText
	 */
	public function Field($properties = array()) {
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
			/** @var FormField $field */
			$field->setDisabled($this->isDisabled());
			$field->setReadonly($this->isReadonly());

			if(count($this->attributes)) {
				foreach($this->attributes as $name => $value) {
					$field->setAttribute($name, $value);
				}
			}

			$content .= $field->FieldHolder();
		}

		if($this->showOnClick) {
			$content .= "</div>\n";
			$content .= "</div>\n";
		}

		return $content;
	}

	/**
	 * Returns the children of this field for use in templating.
	 * @return FieldList
	 */
	public function getChildren() {
		return $this->children;
	}

	/**
	 * Can be empty is a flag that turns on / off empty field checking.
	 *
	 * For example, set this to false (the default) when creating a user account,
	 * and true when displaying on an edit form.
	 *
	 * @param boolean $value
	 *
	 * @return ConfirmedPasswordField
	 */
	public function setCanBeEmpty($value) {
		$this->canBeEmpty = (bool)$value;

		return $this;
	}

	/**
	 * The title on the link which triggers display of the "password" and
	 * "confirm password" formfields. Only used if {@link setShowOnClick()}
	 * is set to TRUE.
	 *
	 * @param string $title
	 *
	 * @return ConfirmedPasswordField
	 */
	public function setShowOnClickTitle($title) {
		$this->showOnClickTitle = $title;

		return $this;
	}

	/**
	 * @return string $title
	 */
	public function getShowOnClickTitle() {
		return $this->showOnClickTitle;
	}

	/**
	 * @param string $title
	 *
	 * @return ConfirmedPasswordField
	 */
	public function setRightTitle($title) {
		foreach($this->children as $field) {
			/** @var FormField $field */
			$field->setRightTitle($title);
		}

		return $this;
	}

	/**
	 * Set child field titles. Titles in order should be:
	 *  - "Current Password" (if getRequireExistingPassword() is set)
	 *  - "Password"
	 *  - "Confirm Password"
	 *
	 * @param array $titles List of child titles
	 * @return $this
	 */
	public function setChildrenTitles($titles) {
		$expectedChildren = $this->getRequireExistingPassword() ? 3 : 2;
		if(is_array($titles) && count($titles) == $expectedChildren) {
			foreach($this->children as $field) {
				if(isset($titles[0])) {
					/** @var FormField $field */
					$field->setTitle($titles[0]);

					array_shift($titles);
				}
			}
		}

		return $this;
	}

	/**
	 * Value is sometimes an array, and sometimes a single value, so we need
	 * to handle both cases.
	 *
	 * @param mixed $value
	 * @param mixed $data
	 * @return $this
	 */
	public function setValue($value, $data = null) {
		// If $data is a DataObject, don't use the value, since it's a hashed value
		if ($data && $data instanceof DataObject) $value = '';

		//store this for later
		$oldValue = $this->value;

		if(is_array($value)) {
			$this->value = $value['_Password'];
			$this->confirmValue = $value['_ConfirmPassword'];
			$this->currentPasswordValue = ($this->getRequireExistingPassword() && isset($value['_CurrentPassword']))
				? $value['_CurrentPassword']
				: null;

			if($this->showOnClick && isset($value['_PasswordFieldVisible'])) {
				$this->children->fieldByName($this->getName() . '[_PasswordFieldVisible]')
					->setValue($value['_PasswordFieldVisible']);
			}
		} else {
			if($value || (!$value && $this->canBeEmpty)) {
				$this->value = $value;
				$this->confirmValue = $value;
			}
		}

		//looking up field by name is expensive, so lets check it needs to change
		if ($oldValue != $this->value) {
			$this->children->fieldByName($this->getName() . '[_Password]')
				->setValue($this->value);

			$this->children->fieldByName($this->getName() . '[_ConfirmPassword]')
				->setValue($this->confirmValue);
		}

		return $this;
	}

	/**
	 * Update the names of the child fields when updating name of field.
	 *
	 * @param string $name new name to give to the field.
	 * @return $this
	 */
	public function setName($name) {
		$this->children->fieldByName($this->getName() . '[_Password]')
				->setName($name . '[_Password]');
		$this->children->fieldByName($this->getName() . '[_ConfirmPassword]')
				->setName($name . '[_ConfirmPassword]');

		return parent::setName($name);
	}

	/**
	 * Determines if the field was actually shown on the client side - if not,
	 * we don't validate or save it.
	 *
	 * @return boolean
	 */
	public function isSaveable() {
		$isVisible = $this->children->fieldByName($this->getName() . '[_PasswordFieldVisible]');

		return (!$this->showOnClick || ($this->showOnClick && $isVisible && $isVisible->Value()));
	}

	/**
	 * Validate this field
	 *
	 * @param Validator $validator
	 * @return bool
	 */
	public function validate($validator) {
		$name = $this->name;

		// if field isn't visible, don't validate
		if(!$this->isSaveable()) {
			return true;
		}

		$passwordField = $this->children->fieldByName($name.'[_Password]');
		$passwordConfirmField = $this->children->fieldByName($name.'[_ConfirmPassword]');
		$passwordField->setValue($this->value);
		$passwordConfirmField->setValue($this->confirmValue);

		$value = $passwordField->Value();

		// both password-fields should be the same
		if($value != $passwordConfirmField->Value()) {
			$validator->validationError(
				$name,
				_t('Form.VALIDATIONPASSWORDSDONTMATCH',"Passwords don't match"),
				"validation"
			);

			return false;
		}

		if(!$this->canBeEmpty) {
			// both password-fields shouldn't be empty
			if(!$value || !$passwordConfirmField->Value()) {
				$validator->validationError(
					$name,
					_t('Form.VALIDATIONPASSWORDSNOTEMPTY', "Passwords can't be empty"),
					"validation"
				);

				return false;
			}
		}

		// lengths
		if(($this->minLength || $this->maxLength)) {
			$errorMsg = null;
			$limit = null;
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
				$validator->validationError(
					$name,
					$errorMsg,
					"validation"
				);
			}
		}

		if($this->requireStrongPassword) {
			if(!preg_match('/^(([a-zA-Z]+\d+)|(\d+[a-zA-Z]+))[a-zA-Z0-9]*$/',$value)) {
				$validator->validationError(
					$name,
					_t('Form.VALIDATIONSTRONGPASSWORD',
						"Passwords must have at least one digit and one alphanumeric character"),
					"validation"
				);

				return false;
			}
		}

		// Check if current password is valid
		if(!empty($value) && $this->getRequireExistingPassword()) {
			if(!$this->currentPasswordValue) {
				$validator->validationError(
					$name,
					_t(
						'ConfirmedPasswordField.CURRENT_PASSWORD_MISSING',
						"You must enter your current password."
					),
					"validation"
				);
				return false;
			}

			// Check this password is valid for the current user
			$member = Member::currentUser();
			if(!$member) {
				$validator->validationError(
					$name,
					_t(
						'ConfirmedPasswordField.LOGGED_IN_ERROR',
						"You must be logged in to change your password."
					),
					"validation"
				);
				return false;
			}

			// With a valid user and password, check the password is correct
			$checkResult = $member->checkPassword($this->currentPasswordValue);
			if(!$checkResult->valid()) {
				$validator->validationError(
					$name,
					_t(
						'ConfirmedPasswordField.CURRENT_PASSWORD_ERROR',
						"The current password you have entered is not correct."
					),
					"validation"
				);
				return false;
			}
		}

		return true;
	}

	/**
	 * Only save if field was shown on the client, and is not empty.
	 *
	 * @param DataObjectInterface $record
	 *
	 * @return boolean
	 */
	public function saveInto(DataObjectInterface $record) {
		if(!$this->isSaveable()) {
			return false;
		}

		if(!($this->canBeEmpty && !$this->value)) {
			parent::saveInto($record);
		}
	}

	/**
	 * Makes a read only field with some stars in it to replace the password
	 *
	 * @return ReadonlyField
	 */
	public function performReadonlyTransformation() {
		$field = $this->castedCopy('ReadonlyField')
			->setTitle($this->title ? $this->title : _t('Member.PASSWORD'))
			->setValue('*****');

		return $field;
	}

	/**
	 * Check if existing password is required
	 *
	 * @return bool
	 */
	public function getRequireExistingPassword() {
		return $this->requireExistingPassword;
	}

	/**
	 * Set if the existing password should be required
	 *
	 * @param bool $show Flag to show or hide this field
	 * @return $this
	 */
	public function setRequireExistingPassword($show) {
		// Don't modify if already added / removed
		if((bool)$show === $this->requireExistingPassword) {
			return $this;
		}
		$this->requireExistingPassword = $show;
		$name = $this->getName();
		$currentName = "{$name}[_CurrentPassword]";
		if ($show) {
			$confirmField = PasswordField::create($currentName, _t('Member.CURRENT_PASSWORD', 'Current Password'));
			$this->children->unshift($confirmField);
		} else {
			$this->children->removeByName($currentName, true);
		}
		return $this;
	}
}
