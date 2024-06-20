<?php

namespace SilverStripe\Security;

use SilverStripe\Control\Director;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Control\Session;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\Validator;

/**
 * Log out form to display to users who arrive at 'Security/logout' without a
 * CSRF token. It's preferable to link to {@link Security::logout_url()}
 * directly - we only use a form so that we can preserve the "BackURL" if set
 */
class LogoutForm extends Form
{
    /**
     * {@inheritdoc}
     */
    public function __construct(
        RequestHandler $controller = null,
        $name = LogoutForm::DEFAULT_NAME,
        FieldList $fields = null,
        FieldList $actions = null,
        Validator $validator = null
    ) {
        $this->setController($controller);

        if (!$fields) {
            $fields = $this->getFormFields();
        }
        if (!$actions) {
            $actions = $this->getFormActions();
        }

        parent::__construct($controller, $name, $fields, $actions);

        $this->setFormAction(Security::logout_url());
    }

    /**
     * Build the FieldList for the logout form
     *
     * @return FieldList
     */
    protected function getFormFields()
    {
        $fields = FieldList::create();

        $controller = $this->getController();
        $backURL = $controller->getBackURL()
            ?: $controller->getReturnReferer();

        // Protect against infinite redirection back to the logout URL after logging out
        if (!$backURL || Director::makeRelative($backURL) === $controller->getRequest()->getURL()) {
            $backURL = Director::baseURL();
        }

        $fields->push(HiddenField::create('BackURL', 'BackURL', $backURL));

        return $fields;
    }

    /**
     * Build default logout form action FieldList
     *
     * @return FieldList
     */
    protected function getFormActions()
    {
        $actions = FieldList::create(
            FormAction::create('doLogout', _t('SilverStripe\\Security\\Member.BUTTONLOGOUT', "Log out"))
        );

        return $actions;
    }
}
