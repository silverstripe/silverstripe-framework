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
class ControllerWithSecurityToken extends Controller implements TestOnly
{

    private static $allowed_actions = array('Form');

    private static $url_handlers = array(
        '$Action//$ID/$OtherID' => "handleAction",
    );

    protected $template = 'BlankPage';

    public function Link($action = null)
    {
        return Controller::join_links(
            'FormTest_ControllerWithSecurityToken',
            $this->getRequest()->latestParam('Action'),
            $this->getRequest()->latestParam('ID'),
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

        return $form;
    }

    public function doSubmit($data, $form, $request)
    {
        $form->sessionMessage('Test save was successful', 'good');
        return $this->redirectBack();
    }
}
