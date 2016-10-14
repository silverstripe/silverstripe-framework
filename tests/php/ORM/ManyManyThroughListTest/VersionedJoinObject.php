<?php

namespace SilverStripe\ORM\Tests\ManyManyThroughListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * @property string $Title
 * @method VersionedObject Parent()
 * @method VersionedItem Child()
 * @mixin Versioned
 */
class VersionedJoinObject extends DataObject implements TestOnly
{
	private static $table_name = 'ManyManyThroughListTest_VersionedJoinObject';

	private static $db = [
		'Title' => 'Varchar'
	];

	private static $extensions = [
		Versioned::class
	];

	private static $has_one = [
		'Parent' => VersionedObject::class,
		'Child' => VersionedItem::class,
	];
}
