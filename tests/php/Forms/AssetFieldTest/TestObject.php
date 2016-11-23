<?php

namespace SilverStripe\Forms\Tests\AssetFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
	private static $table_name = 'AssetFieldTest_TestObject';

	private static $db = array(
		"Title" => "Text",
		"File" => "DBFile",
		"Image" => "DBFile('image/supported')"
	);
}
