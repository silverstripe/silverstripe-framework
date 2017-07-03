<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;

/**
 * @skipUpgrade
 */
class GroupController extends Controller implements TestOnly
{

    private static $allowed_actions = array('Form');

    protected $template = 'BlankPage';

    public function Link($action = null)
    {
        return Controller::join_links('GridFieldDetailFormTest_GroupController', $action, '/');
    }

    public function Form()
    {
        $field = new GridField('testfield', 'testfield', PeopleGroup::get()->sort('Name'));
        /**
 * @skipUpgrade
*/
        $field->getConfig()->addComponent($gridFieldForm = new GridFieldDetailForm($this, 'Form'));
        $field->getConfig()->addComponent(new GridFieldToolbarHeader());
        $field->getConfig()->addComponent(new GridFieldAddNewButton('toolbar-header-right'));
        $field->getConfig()->addComponent(new GridFieldEditButton());
        /**
 * @skipUpgrade
*/
        return new Form($this, 'Form', new FieldList($field), new FieldList());
    }
}
