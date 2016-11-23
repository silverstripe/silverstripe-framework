<?php

namespace SilverStripe\ORM\Tests\VersionedOwnershipTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * @mixin Versioned
 */
class Attachment extends DataObject implements TestOnly
{

	private static $extensions = array(
		Versioned::class,
	);

	private static $table_name = 'VersionedOwnershipTest_Attachment';

	private static $db = array(
		'Title' => 'Varchar(255)',
	);

	private static $belongs_many_many = array(
		'AttachedTo' => 'SilverStripe\\ORM\\Tests\\VersionedOwnershipTest\\Related.Attachments'
	);

	private static $owned_by = array(
		'AttachedTo'
	);
}
