<?php

namespace SilverStripe\ORM\Tests\VersionedOwnershipTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Tests\VersionedOwnershipTest;

/**
 * Object which:
 * - owns a has_one object
 * - owns has_many objects
 */
class Subclass extends TestObject implements TestOnly
{
    private static $db = array(
        'Description' => 'Text',
    );

    private static $has_one = array(
        'Related' => Related::class,
    );

    private static $has_many = array(
        'Banners' => RelatedMany::class,
    );

    private static $table_name = 'VersionedOwnershipTest_Subclass';

    private static $owns = array(
        'Related',
        'Banners',
    );
}
