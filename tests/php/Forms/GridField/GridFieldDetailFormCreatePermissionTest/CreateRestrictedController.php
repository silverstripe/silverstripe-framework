<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldDetailFormCreatePermissionTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;

class CreateRestrictedController extends Controller implements TestOnly
{
    private static $allowed_actions = ['Form'];

    protected $template = 'BlankPage';

    public function __construct()
    {
        parent::__construct();
        $controller = Controller::curr();
        if ($controller) {
            $this->setRequest($controller->getRequest());
        }
    }

    public function Link($action = null)
    {
        return Controller::join_links('GridFieldDetailFormCreatePermissionTest_Controller', $action, '/');
    }

    public function Form()
    {
        $field = new GridField('testfield', 'testfield', CreateRestrictedObject::get());
        $field->getConfig()->addComponent(new GridFieldToolbarHeader());
        $field->getConfig()->addComponent(new GridFieldAddNewButton('toolbar-header-right'));
        $field->getConfig()->addComponent(new GridFieldEditButton());
        $field->getConfig()->addComponent(new GridFieldDetailForm($this, 'Form'));
        return new Form($this, 'Form', new FieldList($field), new FieldList());
    }
}
