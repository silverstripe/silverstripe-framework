<?php


namespace SilverStripe\ORM\Tests\DataObjectDuplicationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;

/**
 * @method ManyManyList Children()
 * @method ManyManyList Parents()
 */
class Class4 extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectDuplicateTest_Class4';

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $many_many = [
        'Children' => Class4::class,
    ];

    private static $belongs_many_many = [
        'Parents' => Class4::class,
    ];
}
