<?php

namespace SilverStripe\ORM\Tests\VersionedOwnershipTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * Object which:
 * - owned by has_many objects
 * - owns many_many Objects
 *
 * @mixin Versioned
 */
class Related extends DataObject implements TestOnly
{
    private static $extensions = array(
        Versioned::class,
    );

    private static $table_name = 'VersionedOwnershipTest_Related';

    private static $db = array(
        'Title' => 'Varchar(255)',
    );

    private static $has_many = array(
        'Parents' => 'SilverStripe\\ORM\\Tests\\VersionedOwnershipTest\\Subclass.Related',
    );

    private static $owned_by = array(
        'Parents',
    );

    private static $many_many = array(
        // Note : Currently unversioned, take care
        'Attachments' => Attachment::class,
    );

    private static $owns = array(
        'Attachments',
    );
}
