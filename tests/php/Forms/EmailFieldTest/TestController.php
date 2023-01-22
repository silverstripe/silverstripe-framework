<?php

namespace SilverStripe\Forms\Tests\EmailFieldTest;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\View\SSViewer;

class TestController extends Controller implements TestOnly
{
    public function __construct()
    {
        parent::__construct();
        if (Controller::has_curr()) {
            $this->setRequest(Controller::curr()->getRequest());
        }
    }

    private static $allowed_actions = ['Form'];

    private static $url_handlers = [
        '$Action//$ID/$OtherID' => "handleAction",
    ];

    protected $template = 'BlankPage';

    public function Link($action = null)
    {
        return Controller::join_links(
            'EmailFieldTest_Controller',
            $this->getRequest()->latestParam('Action'),
            $this->getRequest()->latestParam('ID'),
            $action
        );
    }

    /**
     * @return Form
     */
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
            ),
            new RequiredFields(
                'Email'
            )
        );

        // Disable CSRF protection for easier form submission handling
        $form->disableSecurityToken();

        return $form;
    }

    public function doSubmit($data, Form $form, HTTPRequest $request)
    {
        $form->sessionMessage('Test save was successful', 'good');
        return $this->redirectBack();
    }

    public function getViewer($action = null)
    {
        return new SSViewer('BlankPage');
    }
}
