<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Tests\DataObjectDuplicationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * @method Antelope antelope()
 * @method Bobcat self()
 * @method Goat goat()
 */
class Bobcat extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectDuplicateTest_Bobcat';

    private static $cascade_duplicates = [
        'self',
    ];

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $has_one = [
        'antelope' => Antelope::class,
        'self' => Bobcat::class,
        'goat' => Goat::class,
    ];
}
