<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Control\Controller;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Security\RememberLoginHash;
use SilverStripe\Security\Security;

/**
 * Provides the in-cms session re-authentication form for the "member" authenticator
 */
class CMSMemberLoginForm extends MemberLoginForm
{

    /**
     * CMSMemberLoginForm constructor.
     * @param RequestHandler $controller
     * @param string $authenticatorClass
     * @param FieldList $name
     */
    public function __construct(RequestHandler $controller, $authenticatorClass, $name)
    {
        $this->controller = $controller;

        $this->authenticator_class = $authenticatorClass;

        $fields = $this->getFormFields();

        $actions = $this->getFormActions();

        parent::__construct($controller, $authenticatorClass, $name, $fields, $actions);

        $this->addExtraClass('form--no-dividers');
    }

    /**
     * @return FieldList
     */
    public function getFormFields()
    {
        // Set default fields
        $fields = FieldList::create([
            HiddenField::create("AuthenticationMethod", null, $this->authenticator_class, $this),
            HiddenField::create('tempid', null, $this->controller->getRequest()->requestVar('tempid')),
            PasswordField::create("Password", _t('SilverStripe\\Security\\Member.PASSWORD', 'Password'))
        ]);

        if (Security::config()->get('autologin_enabled')) {
            $fields->insertAfter(
                'Password',
                CheckboxField::create(
                    "Remember",
                    _t('SilverStripe\\Security\\Member.KEEPMESIGNEDIN', "Keep me signed in")
                )->setAttribute(
                    'title',
                    _t(
                        'SilverStripe\\Security\\Member.REMEMBERME',
                        "Remember me next time? (for {count} days on this device)",
                        [ 'count' => RememberLoginHash::config()->uninherited('token_expiry_days') ]
                    )
                )
            );
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
        $actions = FieldList::create([
            FormAction::create('doLogin', _t(__CLASS__ . '.BUTTONLOGIN', "Let me back in"))
                ->addExtraClass('btn-primary'),
            LiteralField::create(
                'doLogout',
                sprintf(
                    '<a class="btn btn-secondary" href="%s" target="_top">%s</a>',
                    Convert::raw2att($logoutLink),
                    _t(__CLASS__ . '.BUTTONLOGOUT', "Log out")
                )
            ),
            LiteralField::create(
                'forgotPassword',
                sprintf(
                    '<a href="%s" class="cms-security__container__form__forgotPassword btn btn-secondary" target="_top">%s</a>',
                    $this->getExternalLink('lostpassword'),
                    _t(__CLASS__ . '.BUTTONFORGOTPASSWORD', "Forgot password")
                )
            )
        ]);

        return $actions;
    }

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
     * @return string
     */
    public function getAuthenticatorName()
    {
        return _t(__CLASS__ . '.AUTHENTICATORNAME', 'CMS Member Login Form');
    }
}
