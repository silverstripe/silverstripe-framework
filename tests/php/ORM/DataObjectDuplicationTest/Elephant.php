<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Tests\DataObjectDuplicationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * @method Antelope Parent()
 * @method Frog Child()
 */
class Elephant extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectDuplicateTest_Elephant';

    private static $cascade_duplicates = [
        'Child',
    ];

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $has_one = [
        'Parent' => Antelope::class,
        'Child' => Frog::class,
    ];
}
