<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\Tests\GridField\GridFieldPrintButtonTest\TestObject;

class GridFieldPrintButtonTest extends SapphireTest
{

    protected static $extra_dataobjects = array(
        TestObject::class,
    );

    protected function setUp()
    {
        parent::setUp();

        // 42 items
        for ($i = 1; $i <= 42; $i++) {
            $obj = new TestObject();
            $obj->Name = "Object {$i}";
            $obj->write();
        }
    }

    public function testLimit()
    {
        $list = TestObject::get();

        $button = new GridFieldPrintButton();
        $button->setPrintColumns(array('Name' => 'My Name'));

        // Get paginated gridfield config
        $config = GridFieldConfig::create()
            ->addComponent(new GridFieldPaginator(10))
            ->addComponent($button);
        $gridField = new GridField('testfield', 'testfield', $list, $config);
        /** @skipUpgrade */
        new Form(Controller::curr(), 'Form', new FieldList($gridField), new FieldList());

        // Printed data should ignore pagination limit
        $printData = $button->generatePrintData($gridField);
        $rows = $printData->ItemRows;
        $this->assertEquals(42, $rows->count());
    }
}
