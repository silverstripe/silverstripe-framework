<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Control\Session;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\PasswordField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\Form;
use SilverStripe\Security\Member;

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
        $backURL = $controller->getBackURL() ?: Session::get('BackURL');

        if (!$fields) {
            $fields = new FieldList();

            // Security/changepassword?h=XXX redirects to Security/changepassword
            // without GET parameter to avoid potential HTTP referer leakage.
            // In this case, a user is not logged in, and no 'old password' should be necessary.
            if (Member::currentUser()) {
                $fields->push(new PasswordField("OldPassword", _t('SilverStripe\\Security\\Member.YOUROLDPASSWORD', "Your old password")));
            }

            $fields->push(new PasswordField("NewPassword1", _t('SilverStripe\\Security\\Member.NEWPASSWORD', "New Password")));
            $fields->push(new PasswordField("NewPassword2", _t('SilverStripe\\Security\\Member.CONFIRMNEWPASSWORD', "Confirm New Password")));
        }
        if (!$actions) {
            $actions = new FieldList(
                new FormAction("doChangePassword", _t('SilverStripe\\Security\\Member.BUTTONCHANGEPASSWORD', "Change Password"))
            );
        }

        if ($backURL) {
            $fields->push(new HiddenField('BackURL', false, $backURL));
        }

        parent::__construct($controller, $name, $fields, $actions);
    }

    /**
     * @return ChangePasswordHandler
     */
    protected function buildRequestHandler()
    {
        return ChangePasswordHandler::create($this);
    }
}
