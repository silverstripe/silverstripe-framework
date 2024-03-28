<?php

namespace SilverStripe\Forms\Tests\GridField;

use LogicException;
use SilverStripe\Control\Controller;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Cheerleader;
use SilverStripe\Forms\Tests\GridField\GridFieldTest\Team;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Versioned\Versioned;

class GridFieldDetailForm_ItemRequestTest extends SapphireTest
{
    protected $usesDatabase = false;

    protected static $fixture_file = 'GridFieldDetailForm_ItemRequestTest.yml';

    protected static $extra_dataobjects = [
        Cheerleader::class,
        Team::class,
    ];

    protected static $required_extensions = [
        Cheerleader::class => [
            Versioned::class,
        ],
    ];

    public function testItemEditFormThrowsException()
    {
        $gridField = new GridField('dummy', 'dummy', new ArrayList(), new GridFieldConfig_Base());
        $modelClass = ArrayData::class;
        $gridField->setModelClass($modelClass);
        $itemRequest = new GridFieldDetailForm_ItemRequest($gridField, new GridFieldDetailForm(), new ArrayData(), new Controller(), '');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Cannot dynamically determine form fields. Pass the fields to GridFieldDetailForm::setFields()'
            . " or implement a getCMSFields() method on $modelClass"
        );

        $itemRequest->ItemEditForm();
    }

    public function testBreadcrumbs()
    {
        $team = Team::get();
        $cheerleader = Cheerleader::get()->first();
        $form = new Form(null, 'Form', new FieldList(), new FieldList());
        $gridField = new GridField('TestGridField', 'TestGridFields', $team);
        $gridField->setForm($form);

        $itemRequest = new GridFieldDetailForm_ItemRequest(
            $gridField,
            new GridFieldDetailForm(),
            $team->first(),
            new LeftandMain(),
            '',
        );

        $item = $itemRequest->Breadcrumbs()->last()->toMap();
        $this->assertTrue(array_key_exists('Extra', $item));
    }
}
