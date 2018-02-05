<?php


namespace SilverStripe\ORM\Tests\DataObjectDuplicationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;

/**
 * @method ManyManyList Children()
 * @method ManyManyList Parents()
 */
class Dingo extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectDuplicateTest_Dingo';

    private static $cascade_duplicates = [
        'Children',
    ];

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $many_many = [
        'Children' => Dingo::class,
    ];

    private static $belongs_many_many = [
        'Parents' => Dingo::class,
    ];
}
