<?php

namespace SilverStripe\Forms\Tests\FormFactoryTest;

use SilverStripe\Control\RequestHandler;
use SilverStripe\Forms\DefaultFormFactory;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;

/**
 * Test factory
 */
class EditFormFactory extends DefaultFormFactory
{
    private static $extensions = [
        ControllerExtension::class
    ];

    protected function getFormFields(RequestHandler $controller = null, $name, $context = [])
    {
        $fields = new FieldList(
            new HiddenField('ID'),
            new TextField('Title')
        );
        $this->invokeWithExtensions('updateFormFields', $fields, $controller, $name, $context);
        return $fields;
    }

    protected function getFormActions(RequestHandler $controller = null, $name, $context = [])
    {
        $actions = new FieldList(
            new FormAction('save', 'Save')
        );
        $this->invokeWithExtensions('updateFormActions', $actions, $controller, $name, $context);
        return $actions;
    }
}
