<?php


namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;

/**
 * Class LostPasswordForm handles the requests for lost password form generation
 *
 * We need the MemberLoginForm for the getFormFields logic.
 */
class LostPasswordForm extends MemberLoginForm
{

    /**
     * Create a single EmailField form that has the capability
     * of using the MemberLoginForm Authenticator
     *
     * @return FieldList
     */
    public function getFormFields()
    {
        return FieldList::create(
            EmailField::create('Email', _t('SilverStripe\\Security\\Member.EMAIL', 'Email'))
        );
    }

    /**
     * Give the member a friendly button to push
     *
     * @return FieldList
     */
    public function getFormActions()
    {
        return FieldList::create(
            FormAction::create(
                'forgotPassword',
                _t('SilverStripe\\Security\\Security.BUTTONSEND', 'Send me the password reset link')
            )
        );
    }
}
