<?php

namespace SilverStripe\Security;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Session;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FormAction;

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

    public function __construct(Controller $controller, $name)
    {
        // Set default fields
        $fields = new FieldList(
            HiddenField::create("AuthenticationMethod", null, $this->authenticator_class, $this),
            HiddenField::create('tempid', null, $controller->getRequest()->requestVar('tempid')),
            PasswordField::create("Password", _t('Member.PASSWORD', 'Password')),
            LiteralField::create(
                'forgotPassword',
                sprintf(
                    '<p id="ForgotPassword"><a href="%s" target="_top">%s</a></p>',
                    $this->getExternalLink('lostpassword'),
                    _t('CMSMemberLoginForm.BUTTONFORGOTPASSWORD', "Forgot password?")
                )
            )
        );

        if (Security::config()->autologin_enabled) {
            $fields->push(CheckboxField::create(
                "Remember",
                _t('Member.REMEMBERME', "Remember me next time?")
            ));
        }

        // Determine returnurl to redirect to parent page
        $logoutLink = $this->getExternalLink('logout');
        if ($returnURL = $controller->getRequest()->requestVar('BackURL')) {
            $logoutLink = Controller::join_links($logoutLink, '?BackURL='.urlencode($returnURL));
        }

        // Make actions
        $actions = new FieldList(
            FormAction::create('dologin', _t('CMSMemberLoginForm.BUTTONLOGIN', "Log back in")),
            LiteralField::create(
                'doLogout',
                sprintf(
                    '<p id="doLogout"><a href="%s" target="_top">%s</a></p>',
                    $logoutLink,
                    _t('CMSMemberLoginForm.BUTTONLOGOUT', "Log out")
                )
            )
        );

        parent::__construct($controller, $name, $fields, $actions);
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
        return _t('CMSMemberLoginForm.AUTHENTICATORNAME', 'CMS Member Login Form');
    }
}
