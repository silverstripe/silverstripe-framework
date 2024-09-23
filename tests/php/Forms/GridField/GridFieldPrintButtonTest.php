<?php

namespace SilverStripe\Forms\Tests\GridField;

use LogicException;
use ReflectionMethod;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\Tests\GridField\GridFieldPrintButtonTest\TestObject;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\ArrayData;

class GridFieldPrintButtonTest extends SapphireTest
{

    protected static $extra_dataobjects = [
        TestObject::class,
    ];

    protected function setUp(): void
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
        $this->assertEquals(42, $this->getTestableRows(TestObject::get())->count());
    }

    public function testCanViewIsRespected()
    {
        $orig = TestObject::$canView;
        TestObject::$canView = false;
        $this->assertEquals(0, $this->getTestableRows(TestObject::get())->count());
        TestObject::$canView = $orig;
    }

    private function getTestableRows($list)
    {
        $button = new GridFieldPrintButton();
        $button->setPrintColumns(['Name' => 'My Name']);

        // Get paginated gridfield config
        $config = GridFieldConfig::create()
            ->addComponent(new GridFieldPaginator(10))
            ->addComponent($button);
        $gridField = new GridField('testfield', 'testfield', $list, $config);
        new Form(Controller::curr(), 'Form', new FieldList($gridField), new FieldList());

        // Printed data should ignore pagination limit
        $printData = $button->generatePrintData($gridField);
        return $printData->ItemRows;
    }

    public function testGeneratePrintData()
    {
        $names = [
            'Bob',
            'Alice',
            'John',
            'Jane',
            'Sam',
        ];

        $list = new ArrayList();
        foreach ($names as $name) {
            $list->add(new ArrayData(['Name' => $name]));
        }

        $rows = $this->getTestableRows($list);

        $foundNames = [];
        foreach ($rows as $row) {
            foreach ($row->ItemRow as $column) {
                $foundNames[] = $column->CellString;
            }
        }

        $this->assertSame($names, $foundNames);
    }

    public function testGetPrintColumnsForGridFieldThrowsException()
    {
        $component = new GridFieldPrintButton();
        $gridField = new GridField('dummy', 'dummy', new ArrayList());
        $gridField->getConfig()->removeComponentsByType(GridFieldDataColumns::class);
        $modelClass = ArrayData::class;
        $gridField->setModelClass($modelClass);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Cannot dynamically determine columns. Add a GridFieldDataColumns component to your GridField'
            . " or implement a summaryFields() method on $modelClass"
        );

        $reflectionMethod = new ReflectionMethod($component, 'getPrintColumnsForGridField');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($component, $gridField);
    }
}
