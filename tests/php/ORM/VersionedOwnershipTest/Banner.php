<?php

namespace SilverStripe\ORM\Tests\VersionedOwnershipTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Tests\VersionedOwnershipTest;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * Banner which doesn't declare its belongs_many_many, but owns an Image
 *
 * @mixin Versioned
 */
class Banner extends DataObject implements TestOnly
{
	private static $extensions = array(
		Versioned::class,
	);

	private static $table_name = 'VersionedOwnershipTest_Banner';

	private static $db = array(
		'Title' => 'Varchar(255)',
	);

	private static $has_one = array(
		'Image' => VersionedOwnershipTest\Image::class,
	);

	private static $owns = array(
		'Image',
	);
}
