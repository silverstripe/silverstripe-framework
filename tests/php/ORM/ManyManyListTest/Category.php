<?php

namespace SilverStripe\ORM\Tests\ManyManyListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ManyManyList;

/**
 * @method ManyManyList Products()
 */
class Category extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyListTest_Category';

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $many_many = [
        'Products' => Product::class
    ];
}
