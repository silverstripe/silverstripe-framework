<?php

namespace SilverStripe\Forms\Tests\FormFactoryTest;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * Edit controller for this form
 */
class TestController extends Controller
{
    private static $extensions = [
        ControllerExtension::class,
    ];

    public function Link($action = null)
    {
        return Controller::join_links(
            'FormFactoryTest_TestController',
            $action,
            '/'
        );
    }

    public function Form()
    {
        // Simple example; Just get the first draft record
        $record = $this->getRecord();
        $factory = new EditFactory();
        return $factory->getForm($this, 'Form', ['Record' => $record]);
    }

    public function save($data, Form $form)
    {
        // Noop
    }

    /**
     * @return DataObject
     */
    protected function getRecord()
    {
        return Versioned::get_by_stage(TestObject::class, Versioned::DRAFT)->first();
    }
}
