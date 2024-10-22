<?php

namespace SilverStripe\Forms\Tests\GridField;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Security\Member;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use SilverStripe\Forms\Tests\GridField\GridFieldDataColumnsTest\TestStatusFlagsObject;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\ArrayData;
use stdClass;

class GridFieldDataColumnsTest extends SapphireTest
{
    public function testGridFieldGetDefaultDisplayFields()
    {
        $obj = new GridField('testfield', 'testfield', Member::get());
        $expected = Member::singleton()->summaryFields();
        $columns = $obj->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $this->assertEquals($expected, $columns->getDisplayFields($obj));
    }

    public function testGridFieldGetDisplayFieldsWithArrayList()
    {
        $list = new ArrayList([new ArrayData(['Title' => 'My Item'])]);
        $obj = new GridField('testfield', 'testfield', $list);
        $expected = ['Title' => 'Title'];
        $columns = $obj->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $columns->setDisplayFields($expected);
        $this->assertEquals($expected, $columns->getDisplayFields($obj));
    }

    public function testGridFieldCustomDisplayFields()
    {
        $obj = new GridField('testfield', 'testfield', Member::get());
        $expected = ['Email' => 'Email'];
        $columns = $obj->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $columns->setDisplayFields($expected);
        $this->assertEquals($expected, $columns->getDisplayFields($obj));
    }

    public function testGridFieldDisplayFieldsWithBadArguments()
    {
        $this->expectException(InvalidArgumentException::class);
        $obj = new GridField('testfield', 'testfield', Member::get());
        $columns = $obj->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $columns->setDisplayFields(new stdClass());
    }

    public function testFieldCasting()
    {
        $obj = new GridField('testfield', 'testfield');
        $columns = $obj->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $this->assertEquals([], $columns->getFieldCasting());
        $columns->setFieldCasting(["MyShortText"=>"Text->FirstSentence"]);
        $this->assertEquals(["MyShortText"=>"Text->FirstSentence"], $columns->getFieldCasting());
    }

    public function testFieldFormatting()
    {
        $obj = new GridField('testfield', 'testfield');
        $columns = $obj->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $this->assertEquals([], $columns->getFieldFormatting());
        $columns->setFieldFormatting(["myFieldName" => '<a href=\"custom-admin/$ID\">$ID</a>']);
        $this->assertEquals(
            ["myFieldName" => '<a href=\"custom-admin/$ID\">$ID</a>'],
            $columns->getFieldFormatting()
        );
    }

    public function testGetDisplayFieldsThrowsException()
    {
        $component = new GridFieldDataColumns();
        $config = new GridFieldConfig_Base();
        $config->addComponent($component);
        $gridField = new GridField('dummy', 'dummy', new ArrayList(), $config);
        $modelClass = ArrayData::class;
        $gridField->setModelClass($modelClass);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Cannot dynamically determine columns. Pass the column names to setDisplayFields()'
            . " or implement a summaryFields() method on $modelClass"
        );

        $component->getDisplayFields($gridField);
    }

    public static function provideGetColumnContentHasStatusFlags(): array
    {
        return [
            'turned off' => [
                'displayStatusFlags' => false,
                'columnsForStatusFlag' => [],
                'columnsHaveFlags' => [
                    'Title' => false,
                    'AnotherField' => false,
                ],
            ],
            'turned on, default col' => [
                'displayStatusFlags' => true,
                'columnsForStatusFlag' => [],
                'columnsHaveFlags' => [
                    'Title' => true,
                    'AnotherField' => false,
                ],
            ],
            'turned on, explicit col' => [
                'displayStatusFlags' => true,
                'columnsForStatusFlag' => ['AnotherField'],
                'columnsHaveFlags' => [
                    'Title' => false,
                    'AnotherField' => true,
                ],
            ],
            'turned on, missing col' => [
                'displayStatusFlags' => true,
                'columnsForStatusFlag' => ['MissingField'],
                'columnsHaveFlags' => [
                    'Title' => false,
                    'AnotherField' => false,
                ],
            ],
        ];
    }

    #[DataProvider('provideGetColumnContentHasStatusFlags')]
    public function testGetColumnContentHasStatusFlags(
        bool $displayStatusFlags,
        array $columnsForStatusFlag,
        array $columnsHaveFlags
    ): void {
        $model = new TestStatusFlagsObject();
        $gridfield = new GridField('testfield', 'testfield', new ArrayList([$model]));
        $columns = $gridfield->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $columns->setDisplayFields(['Title' => 'Title', 'AnotherField' => 'AnotherField']);
        $columns->setDisplayStatusFlags($displayStatusFlags);
        // For this test an empty array means "use the default" - we'll test passing in an empty aray separately.
        if (!empty($columnsForStatusFlag)) {
            $columns->setColumnsForStatusFlag($columnsForStatusFlag);
        }
        // Let columns check for an appropriate column to render the flags in
        $cols = [];
        $columns->augmentColumns($gridfield, $cols);

        $flags = $model->getStatusFlagMarkup('ss-gridfield-badge');
        foreach ($columnsHaveFlags as $column => $hasFlags) {
            if ($hasFlags) {
                $this->assertStringContainsString($flags, $columns->getColumnContent($gridfield, $model, $column));
            } else {
                $this->assertStringNotContainsString($flags, $columns->getColumnContent($gridfield, $model, $column));
            }
        }
    }

    public function testSetColumnsForStatusFlagEmptyArray(): void
    {
        $model = new TestStatusFlagsObject();
        $gridfield = new GridField('testfield', 'testfield', new ArrayList([$model]));
        $columns = $gridfield->getConfig()->getComponentByType(GridFieldDataColumns::class);

        $this->expectException(InvalidArgumentException::class);
        $columns->setColumnsForStatusFlag([]);
    }
}
