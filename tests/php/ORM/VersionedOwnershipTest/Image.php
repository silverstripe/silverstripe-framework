<?php

namespace SilverStripe\ORM\Tests\VersionedOwnershipTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * Simple versioned dataobject
 *
 * @mixin Versioned
 */
class Image extends DataObject implements TestOnly
{
    private static $extensions = array(
        Versioned::class,
    );

    private static $table_name = 'VersionedOwnershipTest_Image';

    private static $db = array(
        'Title' => 'Varchar(255)',
    );
}
