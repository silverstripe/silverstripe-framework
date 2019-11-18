<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\Tip;

class TextFieldTest extends SapphireTest
{

    /**
     * Tests the TextField Max Length Validation Failure
     */
    public function testMaxLengthValidationFail()
    {
        $textField = new TextField('TestField');
        $textField->setMaxLength(5);
        $textField->setValue("John Doe"); // 8 characters, so should fail
        $result = $textField->validate(new RequiredFields());
        $this->assertFalse($result);
    }

    /**
     * Tests the TextField Max Length Validation Success
     */
    public function testMaxLengthValidationSuccess()
    {
        $textField = new TextField('TestField');
        $textField->setMaxLength(5);
        $textField->setValue("John"); // 4 characters, so should pass
        $result = $textField->validate(new RequiredFields());
        $this->assertTrue($result);
    }

    /**
     * Ensures that when a Tip is applied to the field, it outputs it in the schema
     */
    public function testTipIsIncludedInSchema()
    {
        $textField = new TextField('TestField');
        $this->assertArrayNotHasKey('tip', $textField->getSchemaDataDefaults());

        $textField->setTip(new Tip('TestTip'));
        $this->assertArrayHasKey('tip', $textField->getSchemaDataDefaults());
    }
}
