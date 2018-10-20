<?php

namespace SilverStripe\Forms;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\Security\Authenticator;
use SilverStripe\Security\Security;
use SilverStripe\View\HTML;

/**
 * Two masked input fields, checks for matching passwords.
 *
 * Optionally hides the fields by default and shows a link to toggle their
 * visibility.
 *
 * Caution: The form field does not include any JavaScript or CSS when used outside of the CMS context,
 * since the required frontend dependencies are included through CMS bundling.
 */
class ConfirmedPasswordField extends FormField
{

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
     * Caution: The form field does not include any JavaScript or CSS when used outside of the CMS context,
     * since the required frontend dependencies are included through CMS bundling.
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

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_STRUCTURAL;

    /**
     * @var PasswordField
     */
    protected $passwordField = null;

    /**
     * @var PasswordField
     */
    protected $confirmPasswordfield = null;

    /**
     * @var HiddenField
     */
    protected $hiddenField = null;

    /**
     * @param string $name
     * @param string $title
     * @param mixed $value
     * @param Form $form
     * @param boolean $showOnClick
     * @param string $titleConfirmField Alternate title (not localizeable)
     */
    public function __construct(
        $name,
        $title = null,
        $value = "",
        $form = null,
        $showOnClick = false,
        $titleConfirmField = null
    ) {

        // Set field title
        $title = isset($title) ? $title : _t('SilverStripe\\Security\\Member.PASSWORD', 'Password');

        // naming with underscores to prevent values from actually being saved somewhere
        $this->children = FieldList::create(
            $this->passwordField = PasswordField::create(
                "{$name}[_Password]",
                $title
            ),
            $this->confirmPasswordfield = PasswordField::create(
                "{$name}[_ConfirmPassword]",
                (isset($titleConfirmField)) ? $titleConfirmField : _t('SilverStripe\\Security\\Member.CONFIRMPASSWORD', 'Confirm Password')
            )
        );

        // has to be called in constructor because Field() isn't triggered upon saving the instance
        if ($showOnClick) {
            $this->getChildren()->push($this->hiddenField = HiddenField::create("{$name}[_PasswordFieldVisible]"));
        }

        // disable auto complete
        foreach ($this->getChildren() as $child) {
            /** @var FormField $child */
            $child->setAttribute('autocomplete', 'off');
        }

        $this->showOnClick = $showOnClick;

        parent::__construct($name, $title);
        $this->setValue($value);
    }

    public function Title()
    {
        // Title is displayed on nested field, not on the top level field
        return null;
    }

    public function setTitle($title)
    {
        $this->getPasswordField()->setTitle($title);
        return parent::setTitle($title);
    }

    /**
     * @param array $properties
     *
     * @return string
     */
    public function Field($properties = array())
    {
        // Build inner content
        $fieldContent = '';
        foreach ($this->getChildren() as $field) {
            /** @var FormField $field */
            $field->setDisabled($this->isDisabled());
            $field->setReadonly($this->isReadonly());

            if (count($this->attributes)) {
                foreach ($this->attributes as $name => $value) {
                    $field->setAttribute($name, $value);
                }
            }

            $fieldContent .= $field->FieldHolder();
        }

        if (!$this->showOnClick) {
            return $fieldContent;
        }

        if ($this->getShowOnClickTitle()) {
            $title = $this->getShowOnClickTitle();
        } else {
            $title = _t(
                __CLASS__ . '.SHOWONCLICKTITLE',
                'Change Password',
                'Label of the link which triggers display of the "change password" formfields'
            );
        }

        // Check if the field should be visible up front
        $visible = $this->hiddenField->Value();
        $classes = $visible
            ? 'showOnClickContainer'
            : 'showOnClickContainer d-none';

        // Build display holder
        $container = HTML::createTag('div', ['class' => $classes], $fieldContent);
        $actionLink = HTML::createTag('a', ['href' => '#'], $title);
        return HTML::createTag(
            'div',
            ['class' => 'showOnClick'],
            $actionLink . "\n" . $container
        );
    }

    /**
     * Returns the children of this field for use in templating.
     * @return FieldList
     */
    public function getChildren()
    {
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
    public function setCanBeEmpty($value)
    {
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
    public function setShowOnClickTitle($title)
    {
        $this->showOnClickTitle = $title;

        return $this;
    }

    /**
     * @return string $title
     */
    public function getShowOnClickTitle()
    {
        return $this->showOnClickTitle;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setRightTitle($title)
    {
        foreach ($this->getChildren() as $field) {
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
    public function setChildrenTitles($titles)
    {
        $expectedChildren = $this->getRequireExistingPassword() ? 3 : 2;
        if (is_array($titles) && count($titles) === $expectedChildren) {
            foreach ($this->getChildren() as $field) {
                if (isset($titles[0])) {
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
    public function setValue($value, $data = null)
    {
        // If $data is a DataObject, don't use the value, since it's a hashed value
        if ($data && $data instanceof DataObject) {
            $value = '';
        }

        //store this for later
        $oldValue = $this->value;

        if (is_array($value)) {
            $this->value = $value['_Password'];
            $this->confirmValue = $value['_ConfirmPassword'];
            $this->currentPasswordValue = ($this->getRequireExistingPassword() && isset($value['_CurrentPassword']))
                ? $value['_CurrentPassword']
                : null;

            if ($this->showOnClick && isset($value['_PasswordFieldVisible'])) {
                $this->getChildren()->fieldByName($this->getName() . '[_PasswordFieldVisible]')
                    ->setValue($value['_PasswordFieldVisible']);
            }
        } else {
            if ($value || (!$value && $this->canBeEmpty)) {
                $this->value = $value;
                $this->confirmValue = $value;
            }
        }

        //looking up field by name is expensive, so lets check it needs to change
        if ($oldValue != $this->value) {
            $this->getChildren()->fieldByName($this->getName() . '[_Password]')
                ->setValue($this->value);

            $this->getChildren()->fieldByName($this->getName() . '[_ConfirmPassword]')
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
    public function setName($name)
    {
        $this->getPasswordField()->setName($name . '[_Password]');
        $this->getConfirmPasswordField()->setName($name . '[_ConfirmPassword]');
        if ($this->hiddenField) {
            $this->hiddenField->setName($name . '[_PasswordFieldVisible]');
        }

        parent::setName($name);
        return $this;
    }

    /**
     * Determines if the field was actually shown on the client side - if not,
     * we don't validate or save it.
     *
     * @return boolean
     */
    public function isSaveable()
    {
        return !$this->showOnClick
            || ($this->showOnClick && $this->hiddenField && $this->hiddenField->Value());
    }

    /**
     * Validate this field
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        $name = $this->name;

        // if field isn't visible, don't validate
        if (!$this->isSaveable()) {
            return true;
        }

        $this->getPasswordField()->setValue($this->value);
        $this->getConfirmPasswordField()->setValue($this->confirmValue);
        $value = $this->getPasswordField()->Value();

        // both password-fields should be the same
        if ($value != $this->getConfirmPasswordField()->Value()) {
            $validator->validationError(
                $name,
                _t('SilverStripe\\Forms\\Form.VALIDATIONPASSWORDSDONTMATCH', "Passwords don't match"),
                "validation"
            );

            return false;
        }

        if (!$this->canBeEmpty) {
            // both password-fields shouldn't be empty
            if (!$value || !$this->getConfirmPasswordField()->Value()) {
                $validator->validationError(
                    $name,
                    _t('SilverStripe\\Forms\\Form.VALIDATIONPASSWORDSNOTEMPTY', "Passwords can't be empty"),
                    "validation"
                );

                return false;
            }
        }

        // lengths
        $minLength = $this->getMinLength();
        $maxLength = $this->getMaxLength();
        if ($minLength || $maxLength) {
            $errorMsg = null;
            $limit = null;
            if ($minLength && $maxLength) {
                $limit = "{{$minLength},{$maxLength}}";
                $errorMsg = _t(
                    __CLASS__ . '.BETWEEN',
                    'Passwords must be {min} to {max} characters long.',
                    ['min' => $minLength, 'max' => $maxLength]
                );
            } elseif ($minLength) {
                $limit = "{{$minLength}}.*";
                $errorMsg = _t(
                    __CLASS__ . '.ATLEAST',
                    'Passwords must be at least {min} characters long.',
                    ['min' => $minLength]
                );
            } elseif ($maxLength) {
                $limit = "{0,{$maxLength}}";
                $errorMsg = _t(
                    __CLASS__ . '.MAXIMUM',
                    'Passwords must be at most {max} characters long.',
                    ['max' => $maxLength]
                );
            }
            $limitRegex = '/^.' . $limit . '$/';
            if (!empty($value) && !preg_match($limitRegex, $value)) {
                $validator->validationError(
                    $name,
                    $errorMsg,
                    "validation"
                );

                return false;
            }
        }

        if ($this->getRequireStrongPassword()) {
            if (!preg_match('/^(([a-zA-Z]+\d+)|(\d+[a-zA-Z]+))[a-zA-Z0-9]*$/', $value)) {
                $validator->validationError(
                    $name,
                    _t(
                        'SilverStripe\\Forms\\Form.VALIDATIONSTRONGPASSWORD',
                        'Passwords must have at least one digit and one alphanumeric character'
                    ),
                    "validation"
                );

                return false;
            }
        }

        // Check if current password is valid
        if (!empty($value) && $this->getRequireExistingPassword()) {
            if (!$this->currentPasswordValue) {
                $validator->validationError(
                    $name,
                    _t(
                        __CLASS__ . '.CURRENT_PASSWORD_MISSING',
                        'You must enter your current password.'
                    ),
                    "validation"
                );
                return false;
            }

            // Check this password is valid for the current user
            $member = Security::getCurrentUser();
            if (!$member) {
                $validator->validationError(
                    $name,
                    _t(
                        __CLASS__ . '.LOGGED_IN_ERROR',
                        "You must be logged in to change your password."
                    ),
                    "validation"
                );
                return false;
            }

            // With a valid user and password, check the password is correct
            $authenticators = Security::singleton()->getApplicableAuthenticators(Authenticator::CHECK_PASSWORD);
            foreach ($authenticators as $authenticator) {
                $checkResult = $authenticator->checkPassword($member, $this->currentPasswordValue);
                if (!$checkResult->isValid()) {
                    $validator->validationError(
                        $name,
                        _t(
                            __CLASS__ . '.CURRENT_PASSWORD_ERROR',
                            "The current password you have entered is not correct."
                        ),
                        "validation"
                    );
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Only save if field was shown on the client, and is not empty.
     *
     * @param DataObjectInterface $record
     */
    public function saveInto(DataObjectInterface $record)
    {
        if (!$this->isSaveable()) {
            return;
        }

        if (!($this->canBeEmpty && !$this->value)) {
            parent::saveInto($record);
        }
    }

    /**
     * Makes a read only field with some stars in it to replace the password
     *
     * @return ReadonlyField
     */
    public function performReadonlyTransformation()
    {
        /** @var ReadonlyField $field */
        $field = $this->castedCopy(ReadonlyField::class)
            ->setTitle($this->title ? $this->title : _t('SilverStripe\\Security\\Member.PASSWORD', 'Password'))
            ->setValue('*****');

        return $field;
    }

    public function performDisabledTransformation()
    {
        return $this->performReadonlyTransformation();
    }

    /**
     * Check if existing password is required
     *
     * @return bool
     */
    public function getRequireExistingPassword()
    {
        return $this->requireExistingPassword;
    }

    /**
     * Set if the existing password should be required
     *
     * @param bool $show Flag to show or hide this field
     * @return $this
     */
    public function setRequireExistingPassword($show)
    {
        // Don't modify if already added / removed
        if ((bool)$show === $this->requireExistingPassword) {
            return $this;
        }
        $this->requireExistingPassword = $show;
        $name = $this->getName();
        $currentName = "{$name}[_CurrentPassword]";
        if ($show) {
            $confirmField = PasswordField::create($currentName, _t('SilverStripe\\Security\\Member.CURRENT_PASSWORD', 'Current Password'));
            $this->getChildren()->unshift($confirmField);
        } else {
            $this->getChildren()->removeByName($currentName, true);
        }
        return $this;
    }

    /**
     * @return PasswordField
     */
    public function getPasswordField()
    {
        return $this->passwordField;
    }

    /**
     * @return PasswordField
     */
    public function getConfirmPasswordField()
    {
        return $this->confirmPasswordfield;
    }

    /**
     * Set the minimum length required for passwords
     *
     * @param int $minLength
     * @return $this
     */
    public function setMinLength($minLength)
    {
        $this->minLength = (int) $minLength;
        return $this;
    }

    /**
     * @return int
     */
    public function getMinLength()
    {
        return $this->minLength;
    }

    /**
     * Set the maximum length required for passwords
     *
     * @param int $maxLength
     * @return $this
     */
    public function setMaxLength($maxLength)
    {
        $this->maxLength = (int) $maxLength;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * @param bool $requireStrongPassword
     * @return $this
     */
    public function setRequireStrongPassword($requireStrongPassword)
    {
        $this->requireStrongPassword = (bool) $requireStrongPassword;
        return $this;
    }

    /**
     * @return bool
     */
    public function getRequireStrongPassword()
    {
        return $this->requireStrongPassword;
    }
}
