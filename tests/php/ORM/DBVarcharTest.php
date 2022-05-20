<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\NullableField;
use SilverStripe\Forms\TextField;

class DBVarcharTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        DBVarcharTest\TestObject::class,
    ];

    public function testScaffold()
    {
        $obj = new DBVarcharTest\TestObject();
        /** @var TextField $field */
        $field = $obj->dbObject('Title')->scaffoldFormField();
        $this->assertInstanceOf(TextField::class, $field);
        $this->assertEquals(129, $field->getMaxLength());

        /** @var NullableField $nullable */
        $nullable = $obj->dbObject('NullableField')->scaffoldFormField();
        $this->assertInstanceOf(NullableField::class, $nullable);
        $innerField = $nullable->valueField;
        $this->assertInstanceOf(TextField::class, $innerField);
        $this->assertEquals(111, $innerField->getMaxLength());
    }
}
