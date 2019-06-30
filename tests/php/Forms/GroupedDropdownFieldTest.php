<?php declare(strict_types = 1);

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GroupedDropdownField;
use SilverStripe\Forms\RequiredFields;

class GroupedDropdownFieldTest extends SapphireTest
{

    public function testValidation()
    {
        $field = GroupedDropdownField::create(
            'Test',
            'Testing',
            [
                "1" => "One",
                "Group One" => [
                    "2" => "Two",
                    "3" => "Three"
                ],
                "Group Two" => [
                    "4" => "Four"
                ],
            ]
        );

        $this->assertEquals(array("1", "2", "3", "4"), $field->getValidValues());

        $validator = new RequiredFields();

        $field->setValue("1");
        $this->assertTrue($field->validate($validator));

        //test grouped values
        $field->setValue("3");
        $this->assertTrue($field->validate($validator));

        //non-existent value should make the field invalid
        $field->setValue("Over 9000");
        $this->assertFalse($field->validate($validator));

        //empty string shouldn't validate
        $field->setValue('');
        $this->assertFalse($field->validate($validator));

        //empty field should validate after being set
        $field->setEmptyString('Empty String');
        $field->setValue('');
        $this->assertTrue($field->validate($validator));

        //disabled items shouldn't validate
        $field->setDisabledItems(array('1'));
        $field->setValue('1');

        $this->assertEquals(array("2", "3", "4"), $field->getValidValues());
        $this->assertEquals(array("1"), $field->getDisabledItems());

        $this->assertFalse($field->validate($validator));
    }

    /**
     * Test that empty-string values are supported by GroupDropdownTest
     */
    public function testEmptyString()
    {
        // Case A: empty value in the top level of the source
        $field = GroupedDropdownField::create(
            'Test',
            'Testing',
            [
                "" => "(Choose A)",
                "1" => "One",
                "Group One" => [
                    "2" => "Two",
                    "3" => "Three"
                ],
                "Group Two" => [
                    "4" => "Four"
                ],
            ]
        );

        $this->assertRegExp(
            '/<option value="" selected="selected" >\(Choose A\)<\/option>/',
            preg_replace('/\s+/', ' ', (string)$field->Field())
        );

        // Case B: empty value in the nested level of the source
        $field = GroupedDropdownField::create(
            'Test',
            'Testing',
            [
                "1" => "One",
                "Group One" => [
                    "" => "(Choose B)",
                    "2" => "Two",
                    "3" => "Three"
                ],
                "Group Two" => [
                    "4" => "Four"
                ],
            ]
        );
        $this->assertRegExp(
            '/<option value="" selected="selected" >\(Choose B\)<\/option>/',
            preg_replace('/\s+/', ' ', (string)$field->Field())
        );

        // Case C: setEmptyString
        $field = GroupedDropdownField::create(
            'Test',
            'Testing',
            [
                "1" => "One",
                "Group One" => [
                    "2" => "Two",
                    "3" => "Three"
                ],
                "Group Two" => [
                    "4" => "Four"
                ],
            ]
        );
        $field->setEmptyString('(Choose C)');
        $this->assertRegExp(
            '/<option value="" selected="selected" >\(Choose C\)<\/option>/',
            preg_replace('/\s+/', ' ', (string)$field->Field())
        );
    }
}
