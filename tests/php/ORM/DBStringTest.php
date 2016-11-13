<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Core\Object;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBString;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Tests\DBStringTest\MyStringField;

class DBStringTest extends SapphireTest {

	/**
	 * @covers \SilverStripe\ORM\FieldType\DBField::forTemplate()
	 */
	public function testForTemplate() {
		$this->assertEquals(
			"this is<br />\na test!",
			DBField::create_field(MyStringField::class, "this is\na test!")->forTemplate()
		);
	}

	public function testDefault() {
		/** @var DBString $dbField */
		$dbField = Object::create_from_string(
			DBStringTest\MyStringField::class."(['default' => 'Here is my default text'])",
			'Myfield'
		);
		$this->assertEquals(
			"Here is my default text",
			$dbField->getDefaultValue()
		);
	}

	/**
	 * @covers \SilverStripe\ORM\FieldType\DBString::LowerCase()
	 */
	public function testLowerCase() {
		$this->assertEquals(
			'this is a test!',
			DBField::create_field(MyStringField::class, 'This is a TEST!')->LowerCase()
		);
	}

	/**
	 * @covers \SilverStripe\ORM\FieldType\DBString::UpperCase()
	 */
	public function testUpperCase() {
		$this->assertEquals(
			'THIS IS A TEST!',
			DBField::create_field(MyStringField::class, 'This is a TEST!')->UpperCase()
		);
	}

	public function testExists() {
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
