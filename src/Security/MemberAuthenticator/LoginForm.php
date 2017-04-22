<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Control\Director;
use SilverStripe\Control\Session;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Security\RememberLoginHash;
use SilverStripe\Security\LoginForm as BaseLoginForm;
use SilverStripe\View\Requirements;

/**
 * Log-in form for the "member" authentication method.
 *
 * Available extension points:
 * - "authenticationFailed": Called when login was not successful.
 *    Arguments: $data containing the form submission
 * - "forgotPassword": Called before forgot password logic kicks in,
 *    allowing extensions to "veto" execution by returning FALSE.
 *    Arguments: $member containing the detected Member record
 */
class LoginForm extends BaseLoginForm
{

    /**
     * This field is used in the "You are logged in as %s" message
     * @var string
     */
    public $loggedInAsField = 'FirstName';

    /**
     * Required fields for validation
     * @var array
     */
    private static $required_fields;

    /**
     * Constructor
     *
     * @skipUpgrade
     * @param Controller $controller The parent controller, necessary to
     *                               create the appropriate form action tag.
     * @param string $authenticatorClass Authenticator for this LoginForm
     * @param string $name The method on the controller that will return this
     *                     form object.
     * @param FieldList $fields All of the fields in the form - a
     *                                   {@link FieldList} of {@link FormField}
     *                                   objects.
     * @param FieldList|FormAction $actions All of the action buttons in the
     *                                     form - a {@link FieldList} of
     *                                     {@link FormAction} objects
     * @param bool $checkCurrentUser If set to TRUE, it will be checked if a
     *                               the user is currently logged in, and if
     *                               so, only a logout button will be rendered
     */
    public function __construct(
        $controller,
        $authenticatorClass,
        $name,
        $fields = null,
        $actions = null,
        $checkCurrentUser = true
    ) {

        $this->authenticator_class = $authenticatorClass;

        $customCSS = project() . '/css/member_login.css';
        if (Director::fileExists($customCSS)) {
            Requirements::css($customCSS);
        }

        if ($controller->request->getVar('BackURL')) {
            $backURL = $controller->request->getVar('BackURL');
        } else {
            $backURL = Session::get('BackURL');
        }

        if ($checkCurrentUser && Member::currentUser() && Member::logged_in_session_exists()) {
            $fields = FieldList::create(
                HiddenField::create("AuthenticationMethod", null, $this->authenticator_class, $this)
            );
            $actions = FieldList::create(
                FormAction::create("logout", _t('SilverStripe\\Security\\Member.BUTTONLOGINOTHER', "Log in as someone else"))
            );
        } else {
            if (!$fields) {
                $fields = $this->getFormFields();
            }
            if (!$actions) {
                $actions = $this->getFormActions();
            }
        }

        if (isset($backURL)) {
            $fields->push(HiddenField::create('BackURL', 'BackURL', $backURL));
        }

        // Reduce attack surface by enforcing POST requests
        $this->setFormMethod('POST', true);

        parent::__construct($controller, $name, $fields, $actions);

        $this->setValidator(RequiredFields::create(self::config()->get('required_fields')));
    }

    /**
     * Build the FieldList for the login form
     *
     * @return FieldList
     */
    protected function getFormFields()
    {
        $label = Member::singleton()->fieldLabel(Member::config()->unique_identifier_field);
        $fields = FieldList::create(
            HiddenField::create("AuthenticationMethod", null, $this->authenticator_class, $this),
            // Regardless of what the unique identifer field is (usually 'Email'), it will be held in the
            // 'Email' value, below:
            // @todo Rename the field to a more generic covering name
            $emailField = TextField::create("Email", $label, null, null, $this),
            PasswordField::create("Password", _t('SilverStripe\\Security\\Member.PASSWORD', 'Password'))
        );
        $emailField->setAttribute('autofocus', 'true');

        if (Security::config()->remember_username) {
            $emailField->setValue(Session::get('SessionForms.MemberLoginForm.Email'));
        } else {
            // Some browsers won't respect this attribute unless it's added to the form
            $this->setAttribute('autocomplete', 'off');
            $emailField->setAttribute('autocomplete', 'off');
        }
        if (Security::config()->autologin_enabled) {
            $fields->push(
                CheckboxField::create(
                    "Remember",
                    _t('SilverStripe\\Security\\Member.KEEPMESIGNEDIN', "Keep me signed in")
                )->setAttribute(
                    'title',
                    sprintf(
                        _t('SilverStripe\\Security\\Member.REMEMBERME', "Remember me next time? (for %d days on this device)"),
                        RememberLoginHash::config()->uninherited('token_expiry_days')
                    )
                )
            );
        }

        return $fields;
    }

    /**
     * Build default login form action FieldList
     *
     * @return FieldList
     */
    protected function getFormActions()
    {
        $actions = FieldList::create(
            FormAction::create('doLogin', _t('SilverStripe\\Security\\Member.BUTTONLOGIN', "Log in")),
            LiteralField::create(
                'forgotPassword',
                '<p id="ForgotPassword"><a href="' . Security::lost_password_url() . '">'
                . _t('SilverStripe\\Security\\Member.BUTTONLOSTPASSWORD', "I've lost my password") . '</a></p>'
            )
        );

        return $actions;
    }

    public function restoreFormState()
    {
        parent::restoreFormState();

        $forceMessage = Session::get('MemberLoginForm.force_message');
        if (($member = Member::currentUser()) && !$forceMessage) {
            $message = _t(
                'SilverStripe\\Security\\Member.LOGGEDINAS',
                "You're logged in as {name}.",
                array('name' => $member->{$this->loggedInAsField})
            );
            $this->setMessage($message, ValidationResult::TYPE_INFO);
        }

        // Reset forced message
        if ($forceMessage) {
            Session::set('MemberLoginForm.force_message', false);
        }

        return $this;
    }

    /**
     * The name of this login form, to display in the frontend
     * Replaces Authenticator::get_name()
     *
     * @return string
     */
    public function getAuthenticatorName()
    {
        return _t('SilverStripe\\Security\\MemberLoginForm.AUTHENTICATORNAME', "E-mail &amp; Password");
    }
}
