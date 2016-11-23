<?php

namespace SilverStripe\ORM\Tests\VersionedOwnershipTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * @mixin Versioned
 */
class TestObject extends DataObject implements TestOnly
{
	private static $extensions = array(
		Versioned::class,
	);

	private static $table_name = 'VersionedOwnershipTest_Object';

	private static $db = array(
		'Title' => 'Varchar(255)',
		'Content' => 'Text',
	);
}
