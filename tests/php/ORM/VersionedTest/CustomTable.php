<?php

namespace SilverStripe\ORM\Tests\VersionedTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * @mixin Versioned
 */
class CustomTable extends DataObject implements TestOnly
{
	private static $db = [
		'Title' => 'Varchar'
	];

	private static $table_name = 'VTCustomTable';

	private static $extensions = [
		Versioned::class,
	];
}
