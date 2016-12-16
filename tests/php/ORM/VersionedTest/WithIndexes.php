<?php

namespace SilverStripe\ORM\Tests\VersionedTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * @mixin Versioned
 */
class WithIndexes extends DataObject implements TestOnly
{
    private static $table_name = 'VersionedTest_WithIndexes';

    private static $db = array(
        'UniqA' => 'Int',
        'UniqS' => 'Int',
    );

    private static $extensions = array(
        Versioned::class
    );

    private static $indexes = [
        'UniqS_idx' => 'unique ("UniqS")',
        'UniqA_idx' => [
            'type' => 'unique',
            'name' => 'UniqA_idx',
            'value' => '"UniqA"',
        ],
    ];
}
