<?php

namespace SilverStripe\ORM\Tests\ManyManyListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ExtraFieldsObject extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyListTest_ExtraFields';

    private static $many_many = array(
        'Clients' => ExtraFieldsObject::class,
        'Products' => Product::class,
    );

    private static $belongs_many_many = array(
        'WorksWith' => ExtraFieldsObject::class
    );

    private static $many_many_extraFields = array(
        'Clients' => array(
            'Reference' => 'Varchar',
            'Worth' => 'Money'
        ),
        'Products' => array(
            'Reference' => 'Varchar',
        ),
    );
}
