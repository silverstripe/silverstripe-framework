<?php

namespace SilverStripe\ORM\Tests\ChangeSetItemTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * @mixin Versioned
 */
class VersionedObject extends DataObject implements TestOnly
{
    private static $table_name = 'ChangeSetItemTest_Versioned';

    private static $db = [
        'Foo' => 'Int'
    ];

    private static $extensions = [
        Versioned::class
    ];

    function canEdit($member = null)
    {
        return true;
    }
}
