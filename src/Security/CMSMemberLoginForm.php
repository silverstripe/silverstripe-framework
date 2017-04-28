<?php

namespace SilverStripe\Security;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\PasswordField;

/**
 * Provides the in-cms session re-authentication form for the "member" authenticator
 */
class CMSMemberLoginForm extends LoginForm
{
    /**
     * Get link to use for external security actions
     *
     * @param string $action Action
     * @return string
     */
    public function getExternalLink($action = null)
    {
        return Security::singleton()->Link($action);
    }

    /**
     * CMSMemberLoginForm constructor.
     * @param Controller $controller
     * @param string $authenticatorClass
     * @param FieldList $name
     */
    public function __construct(Controller $controller, $authenticatorClass, $name)
    {
        $this->controller = $controller;

        $this->authenticator_class = $authenticatorClass;

        $fields = $this->getFormFields();

        $actions = $this->getFormActions();

        parent::__construct($controller, $name, $fields, $actions);
    }

    /**
     * @return FieldList
     */
    public function getFormFields()
    {
        // Set default fields
        $fields = new FieldList(
            HiddenField::create("AuthenticationMethod", null, $this->authenticator_class, $this),
            HiddenField::create('tempid', null, $this->controller->getRequest()->requestVar('tempid')),
            PasswordField::create("Password", _t('SilverStripe\\Security\\Member.PASSWORD', 'Password')),
            LiteralField::create(
                'forgotPassword',
                sprintf(
                    '<p id="ForgotPassword"><a href="%s" target="_top">%s</a></p>',
                    $this->getExternalLink('lostpassword'),
                    _t('SilverStripe\\Security\\CMSMemberLoginForm.BUTTONFORGOTPASSWORD', "Forgot password?")
                )
            )
        );

        if (Security::config()->autologin_enabled) {
            $fields->push(CheckboxField::create(
                "Remember",
                _t('SilverStripe\\Security\\Member.REMEMBERME', "Remember me next time?")
            ));
        }

        return $fields;
    }

    /**
     * @return FieldList
     */
    public function getFormActions()
    {

        // Determine returnurl to redirect to parent page
        $logoutLink = $this->getExternalLink('logout');
        if ($returnURL = $this->controller->getRequest()->requestVar('BackURL')) {
            $logoutLink = Controller::join_links($logoutLink, '?BackURL=' . urlencode($returnURL));
        }

        // Make actions
        $actions = new FieldList(
            FormAction::create('dologin', _t('SilverStripe\\Security\\CMSMemberLoginForm.BUTTONLOGIN', "Log back in")),
            LiteralField::create(
                'doLogout',
                sprintf(
                    '<p id="doLogout"><a href="%s" target="_top">%s</a></p>',
                    $logoutLink,
                    _t('SilverStripe\\Security\\CMSMemberLoginForm.BUTTONLOGOUT', "Log out")
                )
            )
        );

        return $actions;
    }

    protected function buildRequestHandler()
    {
        return CMSMemberLoginHandler::create($this);
    }

    /**
     * @return string
     */
    public function getAuthenticatorName()
    {
        return _t('SilverStripe\\Security\\CMSMemberLoginForm.AUTHENTICATORNAME', 'CMS Member Login Form');
    }
}
