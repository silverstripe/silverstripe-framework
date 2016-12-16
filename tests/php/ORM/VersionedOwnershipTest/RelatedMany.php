<?php

namespace SilverStripe\ORM\Tests\VersionedOwnershipTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * Object which is owned by a has_one object
 *
 * @mixin Versioned
 */
class RelatedMany extends DataObject implements TestOnly
{
    private static $extensions = array(
        Versioned::class,
    );

    private static $table_name = 'VersionedOwnershipTest_RelatedMany';

    private static $db = array(
        'Title' => 'Varchar(255)',
    );

    private static $has_one = array(
        'Page' => Subclass::class,
    );

    private static $owned_by = array(
        'Page'
    );
}
