<?php

namespace SilverStripe\Forms\Tests\FormTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;

/**
 * @skipUpgrade
 */
class ControllerWithStrictPostCheck extends Controller implements TestOnly
{

    private static $allowed_actions = array('Form');

    protected $template = 'BlankPage';

    public function Link($action = null)
    {
        return Controller::join_links(
            'FormTest_ControllerWithStrictPostCheck',
            $this->request->latestParam('Action'),
            $this->request->latestParam('ID'),
            $action
        );
    }

    public function Form()
    {
        $form = new Form(
            $this,
            'Form',
            new FieldList(
                new EmailField('Email')
            ),
            new FieldList(
                new FormAction('doSubmit')
            )
        );
        $form->setFormMethod('POST');
        $form->setStrictFormMethodCheck(true);
        $form->disableSecurityToken(); // Disable CSRF protection for easier form submission handling

        return $form;
    }

    public function doSubmit($data, $form, $request)
    {
        $form->sessionMessage('Test save was successful', 'good');
        return $this->redirectBack();
    }
}
