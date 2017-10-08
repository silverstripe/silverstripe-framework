<?php

namespace SilverStripe\Forms\Tests\GridField;

use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Security\Member;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
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
     * @covers \SilverStripe\Forms\GridField\GridFieldDataColumns::setDisplayFields
     * @covers \SilverStripe\Forms\GridField\GridFieldDataColumns::getDisplayFields
     */
    public function testGridFieldCustomDisplayFields()
    {
        $obj = new GridField('testfield', 'testfield', Member::get());
        /**
 * @skipUpgrade
*/
        $expected = array('Email' => 'Email');
        $columns = $obj->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $columns->setDisplayFields($expected);
        $this->assertEquals($expected, $columns->getDisplayFields($obj));
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridFieldDataColumns::setDisplayFields
     * @covers \SilverStripe\Forms\GridField\GridFieldDataColumns::getDisplayFields
     *
     * @expectedException \InvalidArgumentException
     */
    public function testGridFieldDisplayFieldsWithBadArguments()
    {
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
        $this->assertEquals(array(), $columns->getFieldCasting());
        $columns->setFieldCasting(array("MyShortText"=>"Text->FirstSentence"));
        $this->assertEquals(array("MyShortText"=>"Text->FirstSentence"), $columns->getFieldCasting());
    }

    /**
     * @covers \SilverStripe\Forms\GridField\GridFieldDataColumns::getFieldFormatting
     * @covers \SilverStripe\Forms\GridField\GridFieldDataColumns::setFieldFormatting
     */
    public function testFieldFormatting()
    {
        $obj = new GridField('testfield', 'testfield');
        $columns = $obj->getConfig()->getComponentByType(GridFieldDataColumns::class);
        $this->assertEquals(array(), $columns->getFieldFormatting());
        $columns->setFieldFormatting(array("myFieldName" => '<a href=\"custom-admin/$ID\">$ID</a>'));
        $this->assertEquals(
            array("myFieldName" => '<a href=\"custom-admin/$ID\">$ID</a>'),
            $columns->getFieldFormatting()
        );
    }
}
