<?php

namespace SilverStripe\ORM\Tests\ChangeSetTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * @mixin Versioned
 */
class MidObject extends DataObject implements TestOnly
{
    use Permissions;

    private static $table_name = 'ChangeSetTest_Mid';

    private static $db = [
        'Bar' => 'Int',
    ];

    private static $has_one = [
        'Base' => BaseObject::class,
        'End' => EndObject::class,
    ];

    private static $owns = [
        'End',
    ];

    private static $extensions = [
        Versioned::class,
    ];
}
