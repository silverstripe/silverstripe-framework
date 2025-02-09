<?php

namespace SilverStripe\Forms\Tests\GridField;

use LogicException;
use ReflectionMethod;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\Tests\GridField\GridFieldPrintButtonTest\TestObject;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\FieldType\DBHTMLVarchar;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\View\ArrayData;

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

    public function provideHandlePrintEscaping(): array
    {
        return [
            // Without data columns component
            'raw string pre-escaped' => [
                'value' => 'before&lt;script&gt;alert("hehehe");&lt;/script&gt;after&amp;',
                'useGridFieldDataColumns' => false,
                'expected' => 'before&amp;lt;script&amp;gt;alert("hehehe");&amp;lt;/script&amp;gt;after&amp;amp;',
            ],
            'raw string as HTML' => [
                'value' => 'before<script>alert("hehehe");</script>after&amp;',
                'useGridFieldDataColumns' => false,
                'expected' => 'before&lt;script&gt;alert("hehehe");&lt;/script&gt;after&amp;amp;',
            ],
            'DBText pre-escaped' => [
                'value' => (new DBText('field'))->setValue('before&lt;script&gt;alert("hehehe");&lt;/script&gt;after&amp;'),
                'useGridFieldDataColumns' => false,
                'expected' => 'before&amp;lt;script&amp;gt;alert("hehehe");&amp;lt;/script&amp;gt;after&amp;amp;',
            ],
            'DBText as HTML' => [
                'value' => (new DBText('field'))->setValue('before<script>alert("hehehe");</script>after&amp;'),
                'useGridFieldDataColumns' => false,
                'expected' => 'before&lt;script&gt;alert("hehehe");&lt;/script&gt;after&amp;amp;',
            ],
            'DBHTMLText pre-escaped' => [
                'value' => (new DBHTMLText('field'))->setValue('before&lt;script&gt;alert("hehehe");&lt;/script&gt;after&amp;'),
                'useGridFieldDataColumns' => false,
                'expected' => 'before&amp;lt;script&amp;gt;alert("hehehe");&amp;lt;/script&amp;gt;after&amp;amp;',
            ],
            'DBHTMLText as HTML' => [
                'value' => (new DBHTMLText('field'))->setValue('before<script>alert("hehehe");</script>after&amp;'),
                'useGridFieldDataColumns' => false,
                'expected' => 'before&lt;script&gt;alert("hehehe");&lt;/script&gt;after&amp;amp;',
            ],
            'DBHTMLVarchar pre-escaped' => [
                'value' => (new DBHTMLVarchar('field'))->setValue('before&lt;script&gt;alert("hehehe");&lt;/script&gt;after&amp;'),
                'useGridFieldDataColumns' => false,
                'expected' => 'before&amp;lt;script&amp;gt;alert("hehehe");&amp;lt;/script&amp;gt;after&amp;amp;',
            ],
            'DBHTMLVarchar as HTML' => [
                'value' => (new DBHTMLVarchar('field'))->setValue('before<script>alert("hehehe");</script>after&amp;'),
                'useGridFieldDataColumns' => false,
                'expected' => 'before&lt;script&gt;alert("hehehe");&lt;/script&gt;after&amp;amp;',
            ],
            // With data columns component
            'raw string pre-escaped with datacolumns' => [
                'value' => 'before&lt;script&gt;alert("hehehe");&lt;/script&gt;after&amp;',
                'useGridFieldDataColumns' => true,
                'expected' => 'before&amp;lt;script&amp;gt;alert("hehehe");&amp;lt;/script&amp;gt;after&amp;amp;',
            ],
            'raw string pre-escaped with datacolumns' => [
                'value' => 'before<script>alert("hehehe");</script>after&amp;',
                'useGridFieldDataColumns' => true,
                'expected' => 'beforealert("hehehe");after&amp;amp;',
            ],
            'DBText pre-escaped with datacolumns' => [
                'value' => (new DBText('field'))->setValue('before&lt;script&gt;alert("hehehe");&lt;/script&gt;after&amp;'),
                'useGridFieldDataColumns' => true,
                'expected' => 'before&amp;lt;script&amp;gt;alert("hehehe");&amp;lt;/script&amp;gt;after&amp;amp;',
            ],
            'DBText as HTML with datacolumns' => [
                'value' => (new DBText('field'))->setValue('before<script>alert("hehehe");</script>after&amp;'),
                'useGridFieldDataColumns' => true,
                // Note stripped tags here
                'expected' => 'beforealert("hehehe");after&amp;amp;',
            ],
            'DBHTMLText pre-escaped with datacolumns' => [
                'value' => (new DBHTMLText('field'))->setValue('before&lt;script&gt;alert("hehehe");&lt;/script&gt;after&amp;'),
                'useGridFieldDataColumns' => true,
                'expected' => 'before&amp;lt;script&amp;gt;alert("hehehe");&amp;lt;/script&amp;gt;after&amp;amp;',
            ],
            'DBHTMLText as HTML with datacolumns' => [
                'value' => (new DBHTMLText('field'))->setValue('before<script>alert("hehehe");</script>after&amp;'),
                'useGridFieldDataColumns' => true,
                // Note stripped tags here
                'expected' => 'beforealert("hehehe");after&amp;amp;',
            ],
            'DBHTMLVarchar pre-escaped with datacolumns' => [
                'value' => (new DBHTMLVarchar('field'))->setValue('before&lt;script&gt;alert("hehehe");&lt;/script&gt;after&amp;'),
                'useGridFieldDataColumns' => true,
                'expected' => 'before&amp;lt;script&amp;gt;alert("hehehe");&amp;lt;/script&amp;gt;after&amp;amp;',
            ],
            'DBHTMLVarchar as HTML with datacolumns' => [
                'value' => (new DBHTMLVarchar('field'))->setValue('before<script>alert("hehehe");</script>after&amp;'),
                'useGridFieldDataColumns' => true,
                // Note stripped tags here
                'expected' => 'beforealert("hehehe");after&amp;amp;',
            ],
        ];
    }

    /**
     * Explicitly tests that the following are both true:
     * - XML entities are not double-escaped
     * - XSS attack vectors are not introduced
     *
     * @dataProvider provideHandlePrintEscaping
     */
    public function testHandlePrintEscaping(string|DBField $value, bool $useGridFieldDataColumns, string $expected): void
    {
        $component = new GridFieldPrintButton();
        $component->getPrintColumns();

        $list = new ArrayList([new ArrayData(['Name' => $value])]);

        $button = new GridFieldPrintButton();
        $button->setPrintColumns(['Name' => 'My Name']);

        // Get paginated gridfield config
        $config = GridFieldConfig::create()
            ->addComponent(new GridFieldPaginator(10))
            ->addComponent($button);
        if ($useGridFieldDataColumns) {
            // If this component is present, GridFieldPrintButton uses it to get the value,
            // and that includes some transformation of the value including escaping.
            // So we need to check both with and without the component to ensure both scenarios
            // present sane results.
            $columns = new GridFieldDataColumns();
            $columns->setDisplayFields(['Name' => 'My Name']);
            $config->addComponent($columns);
        }
        $gridField = new GridField('testfield', 'testfield', $list, $config);
        new Form(Controller::curr(), 'Form', new FieldList($gridField), new FieldList());

        // Printed data should ignore pagination limit
        $result = $button->handlePrint($gridField);

        $parser = new CSSContentParser($result->__toString());
        $cellContent = $parser->getBySelector('td');

        $this->assertCount(1, $cellContent);
        $this->assertSame("<td>{$expected}</td>", $cellContent[0]->asXML());
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
