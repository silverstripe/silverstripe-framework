<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBTime;

class TimeTest extends SapphireTest {

	public function testNice() {
		$time = DBField::create_field('Time', '17:15:55');
		$this->assertEquals('5:15pm', $time->Nice());

		DBTime::config()->update('nice_format', 'H:i:s');
		$this->assertEquals('17:15:55', $time->Nice());
	}

}
