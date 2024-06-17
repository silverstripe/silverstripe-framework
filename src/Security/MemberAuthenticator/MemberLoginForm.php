<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Control\Director;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\LoginForm as BaseLoginForm;
use SilverStripe\Security\Member;
use SilverStripe\Security\RememberLoginHash;
use SilverStripe\Security\Security;
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
class MemberLoginForm extends BaseLoginForm
{

    /**
     * This field is used in the "You are logged in as %s" message
     * @var string
     */
    public $loggedInAsField = 'FirstName';

    /**
     * Required fields for validation
     *
     * @config
     * @var array
     */
    private static $required_fields = [
        'Email',
        'Password',
    ];

    /**
     * Constructor
     *
     * @param RequestHandler $controller The parent controller, necessary to
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
        $this->setController($controller);
        $this->setAuthenticatorClass($authenticatorClass);

        $customCSS = project() . '/css/member_login.css';
        if (Director::fileExists($customCSS)) {
            Requirements::css($customCSS);
        }

        if ($checkCurrentUser && Security::getCurrentUser()) {
            $logoutAction = Security::logout_url();
            $fields = FieldList::create(
                HiddenField::create('AuthenticationMethod', null, $this->getAuthenticatorClass(), $this)
            );
            $actions = FieldList::create(
                FormAction::create('logout', _t(
                    'SilverStripe\\Security\\Member.BUTTONLOGINOTHER',
                    'Log in as someone else'
                ))
            );
        } else {
            if (!$fields) {
                $fields = $this->getFormFields();
            }
            if (!$actions) {
                $actions = $this->getFormActions();
            }
        }

        // Reduce attack surface by enforcing POST requests
        $this->setFormMethod('POST', true);

        parent::__construct($controller, $name, $fields, $actions);

        if (isset($logoutAction)) {
            $this->setFormAction($logoutAction);
        }
        $this->setValidator(RequiredFields::create(static::config()->get('required_fields')));
    }

    /**
     * Build the FieldList for the login form
     *
     * @return FieldList
     */
    protected function getFormFields()
    {
        $request = $this->getRequest();
        if ($request->getVar('BackURL')) {
            $backURL = $request->getVar('BackURL');
        } else {
            $backURL = $request->getSession()->get('BackURL');
        }

        $label = Member::singleton()->fieldLabel(Member::config()->get('unique_identifier_field'));
        $fields = FieldList::create(
            HiddenField::create("AuthenticationMethod", null, $this->getAuthenticatorClass(), $this),
            // Regardless of what the unique identifier field is (usually 'Email'), it will be held in the
            // 'Email' value, below:
            $emailField = TextField::create("Email", $label, null, null, $this),
            PasswordField::create("Password", _t('SilverStripe\\Security\\Member.PASSWORD', 'Password'))
        );
        $emailField->setAttribute('autofocus', 'true');

        if (Security::config()->get('remember_username')) {
            $emailField->setValue($this->getSession()->get('SessionForms.MemberLoginForm.Email'));
        } else {
            // Some browsers won't respect this attribute unless it's added to the form
            $this->setAttribute('autocomplete', 'off');
            $emailField->setAttribute('autocomplete', 'off');
        }
        if (Security::config()->get('autologin_enabled')) {
            $fields->push(
                CheckboxField::create(
                    "Remember",
                    _t(
                        'SilverStripe\\Security\\Member.KEEP_ME_SIGNED_IN',
                        'Keep me signed in for {count} days',
                        [ 'count' => RememberLoginHash::config()->uninherited('token_expiry_days') ]
                    )
                )
                    ->setAttribute(
                        'title',
                        _t(
                            'SilverStripe\\Security\\Member.KEEP_ME_SIGNED_IN_TOOLTIP',
                            'You will remain authenticated on this device for {count} days. Only use this feature if you trust the device you are using.',
                            ['count' => RememberLoginHash::config()->uninherited('token_expiry_days')]
                        )
                    )
            );
        }

        if (isset($backURL)) {
            $fields->push(HiddenField::create('BackURL', 'BackURL', $backURL));
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

        $session = $this->getSession();
        $forceMessage = $session->get('MemberLoginForm.force_message');
        if (($member = Security::getCurrentUser()) && !$forceMessage) {
            $message = _t(
                'SilverStripe\\Security\\Member.LOGGEDINAS',
                "You're logged in as {name}.",
                ['name' => $member->{$this->loggedInAsField}]
            );
            $this->setMessage($message, ValidationResult::TYPE_INFO);
        }

        // Reset forced message
        if ($forceMessage) {
            $session->set('MemberLoginForm.force_message', false);
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
        return _t(MemberLoginForm::class . '.AUTHENTICATORNAME', "E-mail & Password");
    }
}
