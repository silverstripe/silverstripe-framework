<?php

namespace SilverStripe\ORM\Tests\DataDifferencerTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class HasOneRelationObject extends DataObject implements TestOnly
{
	private static $table_name = 'DataDifferencerTest_HasOneRelationObject';

	private static $db = array(
		'Title' => 'Varchar'
	);

	private static $has_many = array(
		'Objects' => TestObject::class
	);
}
