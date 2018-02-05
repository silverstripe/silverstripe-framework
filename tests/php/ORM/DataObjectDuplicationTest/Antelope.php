<?php

namespace SilverStripe\ORM\Tests\DataObjectDuplicationTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\ORM\ManyManyList;

/**
 * @method HasManyList bobcats()
 * @method ManyManyList caribou()
 * @method Elephant elephant()
 */
class Antelope extends DataObject implements TestOnly
{
    private static $table_name = 'DataObjectDuplicateTest_Antelope';

    private static $cascade_duplicates = [
        'bobcats',
        'caribou',
        'elephant',
    ];

    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $has_many = [
        'bobcats' => Bobcat::class,
    ];

    private static $many_many = [
        'caribou' => Caribou::class,
    ];

    private static $many_many_extraFields = [
        'caribou' => [
            'Sort' => 'Int',
        ],
    ];

    private static $belongs_to = [
        'elephant' => Elephant::class,
    ];
}
