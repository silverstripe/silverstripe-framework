<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Control\RequestHandler;
use SilverStripe\Control\Session;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Security\Security;

/**
 * Standard Change Password Form
 */
class ChangePasswordForm extends Form
{
    /**
     * Constructor
     *
     * @param RequestHandler $controller The parent controller, necessary to create the appropriate form action tag.
     * @param string $name The method on the controller that will return this form object.
     * @param FieldList|FormField $fields All of the fields in the form - a {@link FieldList} of
     * {@link FormField} objects.
     * @param FieldList|FormAction $actions All of the action buttons in the form - a {@link FieldList} of
     */
    public function __construct($controller, $name, $fields = null, $actions = null)
    {
        $backURL = $controller->getBackURL()
            ?: $controller->getRequest()->getSession()->get('BackURL');

        if (!$fields) {
            $fields = $this->getFormFields();
        }
        if (!$actions) {
            $actions = $this->getFormActions();
        }

        if ($backURL) {
            $fields->push(HiddenField::create('BackURL', false, $backURL));
        }

        parent::__construct($controller, $name, $fields, $actions);
    }

    /**
     * @return FieldList
     */
    protected function getFormFields()
    {
        $fields = FieldList::create();

        // Security/changepassword?h=XXX redirects to Security/changepassword
        // without GET parameter to avoid potential HTTP referer leakage.
        // In this case, a user is not logged in, and no 'old password' should be necessary.
        if (Security::getCurrentUser()) {
            $fields->push(PasswordField::create('OldPassword', _t('SilverStripe\\Security\\Member.YOUROLDPASSWORD', 'Your old password')));
        }

        $fields->push(PasswordField::create('NewPassword1', _t('SilverStripe\\Security\\Member.NEWPASSWORD', 'New Password')));
        $fields->push(PasswordField::create('NewPassword2', _t('SilverStripe\\Security\\Member.CONFIRMNEWPASSWORD', 'Confirm New Password')));

        return $fields;
    }

    /**
     * @return FieldList
     */
    protected function getFormActions()
    {
        $actions = FieldList::create(
            FormAction::create(
                'doChangePassword',
                _t('SilverStripe\\Security\\Member.BUTTONCHANGEPASSWORD', 'Change Password')
            )
        );

        return $actions;
    }
}
