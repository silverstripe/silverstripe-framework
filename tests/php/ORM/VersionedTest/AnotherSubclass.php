<?php

namespace SilverStripe\ORM\Tests\VersionedTest;

use SilverStripe\Dev\TestOnly;

class AnotherSubclass extends TestObject implements TestOnly
{
	private static $table_name = 'VersionedTest_AnotherSubclass';

	private static $db = array(
		"AnotherField" => "Varchar"
	);
}
