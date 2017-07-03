<?php

namespace SilverStripe\Forms\Tests\GridField\GridFieldDetailFormTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;
use SilverStripe\Forms\TextField;

/**
 * @skipUpgrade
 */
class CategoryController extends Controller implements TestOnly
{
    private static $allowed_actions = array('Form');

    protected $template = 'BlankPage';

    public function Link($action = null)
    {
        return Controller::join_links('GridFieldDetailFormTest_CategoryController', $action, '/');
    }

    public function Form()
    {
        // GridField lists categories for a specific person
        $person = Person::get()->sort('FirstName')->First();
        $detailFields = singleton(Category::class)->getCMSFields();
        $detailFields->addFieldsToTab(
            'Root.Main',
            array(
                new CheckboxField('ManyMany[IsPublished]'),
                new TextField('ManyMany[PublishedBy]')
            )
        );
        $categoriesField = new GridField('testfield', 'testfield', $person->Categories());
        $categoriesField->getConfig()->addComponent(
            $gridFieldForm = new GridFieldDetailForm(
                $this,
                'SilverStripe\\Forms\\Form'
            )
        );
        $gridFieldForm->setFields($detailFields);
        $categoriesField->getConfig()->addComponent(new GridFieldToolbarHeader());
        $categoriesField->getConfig()->addComponent(new GridFieldAddNewButton('toolbar-header-right'));
        $categoriesField->getConfig()->addComponent(new GridFieldEditButton());

        $favGroupsField = new GridField('testgroupsfield', 'testgroupsfield', $person->FavouriteGroups());
        /** @skipUpgrade */
        $favGroupsField->getConfig()->addComponent(new GridFieldDetailForm($this, 'Form'));
        $favGroupsField->getConfig()->addComponent(new GridFieldToolbarHeader());
        $favGroupsField->getConfig()->addComponent(new GridFieldAddNewButton('toolbar-header-right'));
        $favGroupsField->getConfig()->addComponent(new GridFieldEditButton());

        $fields = new FieldList($categoriesField, $favGroupsField);
        /** @skipUpgrade */
        return new Form($this, 'Form', $fields, new FieldList());
    }
}
