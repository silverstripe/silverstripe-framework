<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBString;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Tests\DBStringTest\MyStringField;
use PHPUnit\Framework\Attributes\DataProvider;

class DBStringTest extends SapphireTest
{

    public function testForTemplate()
    {
        $this->assertEquals(
            "this is<br />\na test!",
            DBField::create_field(MyStringField::class, "this is\na test!")->forTemplate()
        );
    }

    public function testDefault()
    {
        /** @var DBString $dbField */
        $dbField = Injector::inst()->create(
            DBStringTest\MyStringField::class . "(['default' => 'Here is my default text'])",
            'Myfield'
        );
        $this->assertEquals(
            "Here is my default text",
            $dbField->getDefaultValue()
        );
    }

    public function testLowerCase()
    {
        /** @var MyStringField $field */
        $field = DBField::create_field(MyStringField::class, 'This is a TEST!');
        $this->assertEquals(
            'this is a test!',
            $field->LowerCase()
        );
    }

    public function testUpperCase()
    {
        /** @var MyStringField $field */
        $field = DBField::create_field(MyStringField::class, 'This is a TEST!');
        $this->assertEquals(
            'THIS IS A TEST!',
            $field->UpperCase()
        );
    }

    public function testExists()
    {
        // True exists
        $this->assertTrue(DBField::create_field(MyStringField::class, true)->exists());
        $this->assertTrue(DBField::create_field(MyStringField::class, '0')->exists());
        $this->assertTrue(DBField::create_field(MyStringField::class, '1')->exists());
        $this->assertTrue(DBField::create_field(MyStringField::class, 1)->exists());
        $this->assertTrue(DBField::create_field(MyStringField::class, 1.1)->exists());

        // false exists
        $this->assertFalse(DBField::create_field(MyStringField::class, false)->exists());
        $this->assertFalse(DBField::create_field(MyStringField::class, '')->exists());
        $this->assertFalse(DBField::create_field(MyStringField::class, null)->exists());
        $this->assertFalse(DBField::create_field(MyStringField::class, 0)->exists());
        $this->assertFalse(DBField::create_field(MyStringField::class, 0.0)->exists());
    }
}
