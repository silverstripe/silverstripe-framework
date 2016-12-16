<?php

namespace SilverStripe\ORM\Tests\ChangeSetTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * @mixin Versioned
 */
class EndObjectChild extends EndObject implements TestOnly
{
    private static $table_name = 'ChangeSetTest_EndObjectChild';

    private static $db = [
        'Qux' => 'Int',
    ];
}
