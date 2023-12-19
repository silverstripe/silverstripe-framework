<?php

namespace SilverStripe\Forms\Tests\GridField;

use InvalidArgumentException;
use LogicException;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Security\Member;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_Base;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use stdClass;

class GridFieldDataColumnsTest extends SapphireTest
{
    /**
     * @covers \SilverStripe\Forms\GridField\GridFieldDataColumns::getDisplayFields
     */
    public function testGridFieldGetDefaultDisplayFields()
    {
        $obj = new GridField('testfield', 'testfield', Member::get());
        $expected = Member::singleton()->summaryFields();
        $columns = $obj->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $this->assertEquals($expected, $columns->getDisplayFields($obj));
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridFieldDataColumns::getDisplayFields
     */
    public function testGridFieldGetDisplayFieldsWithArrayList()
    {
        $list = new ArrayList([new ArrayData(['Title' => 'My Item'])]);
        $obj = new GridField('testfield', 'testfield', $list);
        $expected = ['Title' => 'Title'];
        $columns = $obj->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $columns->setDisplayFields($expected);
        $this->assertEquals($expected, $columns->getDisplayFields($obj));
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridFieldDataColumns::setDisplayFields
     * @covers \SilverStripe\Forms\GridField\GridFieldDataColumns::getDisplayFields
     */
    public function testGridFieldCustomDisplayFields()
    {
        $obj = new GridField('testfield', 'testfield', Member::get());
        $expected = ['Email' => 'Email'];
        $columns = $obj->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $columns->setDisplayFields($expected);
        $this->assertEquals($expected, $columns->getDisplayFields($obj));
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridFieldDataColumns::setDisplayFields
     * @covers \SilverStripe\Forms\GridField\GridFieldDataColumns::getDisplayFields
     */
    public function testGridFieldDisplayFieldsWithBadArguments()
    {
        $this->expectException(InvalidArgumentException::class);
        $obj = new GridField('testfield', 'testfield', Member::get());
        $columns = $obj->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $columns->setDisplayFields(new stdClass());
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridFieldDataColumns::getFieldCasting
     * @covers \SilverStripe\Forms\GridField\GridFieldDataColumns::setFieldCasting
     */
    public function testFieldCasting()
    {
        $obj = new GridField('testfield', 'testfield');
        $columns = $obj->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $this->assertEquals([], $columns->getFieldCasting());
        $columns->setFieldCasting(["MyShortText"=>"Text->FirstSentence"]);
        $this->assertEquals(["MyShortText"=>"Text->FirstSentence"], $columns->getFieldCasting());
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridFieldDataColumns::getFieldFormatting
     * @covers \SilverStripe\Forms\GridField\GridFieldDataColumns::setFieldFormatting
     */
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
}
