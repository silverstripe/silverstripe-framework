<?php

namespace SilverStripe\ORM\Tests\ManyManyListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ExtraFieldsObject extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyListTest_ExtraFields';

    private static $many_many = [
        'Clients' => ExtraFieldsObject::class,
        'Products' => Product::class,
    ];

    private static $belongs_many_many = [
        'WorksWith' => ExtraFieldsObject::class
    ];

    private static $many_many_extraFields = [
        'Clients' => [
            'Reference' => 'Varchar',
            'Worth' => 'Money'
        ],
        'Products' => [
            'Reference' => 'Varchar',
        ],
    ];
}
