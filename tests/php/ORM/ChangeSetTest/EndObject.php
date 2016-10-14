<?php

namespace SilverStripe\ORM\Tests\ChangeSetTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * @mixin Versioned
 */
class EndObject extends DataObject implements TestOnly
{
	use Permissions;

	private static $table_name = 'ChangeSetTest_End';

	private static $db = [
		'Baz' => 'Int',
	];

	private static $extensions = [
		Versioned::class,
	];
}
