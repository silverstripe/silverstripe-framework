<?php

namespace SilverStripe\Forms\Tests\DBFileTest;

use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * @property DBFile $MyFile
 */
class TestObject extends DataObject implements TestOnly
{
	private static $table_name = 'DBFileTest_TestObject';

	private static $db = array(
		"MyFile" => "DBFile"
	);
}
