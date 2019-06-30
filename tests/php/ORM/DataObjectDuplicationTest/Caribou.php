<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Tests\DataObjectDuplicationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;

/**
 * @method ManyManyList antelopes()
 */
class Caribou extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectDuplicateTest_Caribou';

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $belongs_many_many = [
        'antelopes' => Antelope::class,
    ];
}
