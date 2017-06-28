<?php

namespace SilverStripe\ORM\Tests\ManyManyListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Product extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyListTest_Product';

    private static $db = array(
        'Title' => 'Varchar'
    );

    private static $many_many = array(
        'RelatedProducts' => Product::class
    );

    private static $belongs_many_many = array(
        'RelatedTo' => Product::class,
        'Categories' => Category::class
    );

    private static $default_sort = '"Title" IS NOT NULL ASC, "Title" ASC';
}
