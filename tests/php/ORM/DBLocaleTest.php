<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Dev\SapphireTest;

class DBLocaleTest extends SapphireTest {
	public function testNice() {
		$l = DBField::create_field('Locale', 'de_DE');
		$this->assertEquals($l->Nice(), 'German');
	}

	public function testNiceNative() {
		$l = DBField::create_field('Locale', 'de_DE');
		$this->assertEquals($l->Nice(true), 'Deutsch');
	}

	public function testNativeName() {
		$l = DBField::create_field('Locale', 'de_DE');
		$this->assertEquals($l->getNativeName(), 'Deutsch');
	}
}
