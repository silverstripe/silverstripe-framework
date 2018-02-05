<?php


namespace SilverStripe\ORM\Tests\DataObjectDuplicationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * Note: Not duplicated
 */
class Goat extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectDuplicateTest_Goat';

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $belongs_to = [
        'bobcats' => Bobcat::class,
    ];
}
