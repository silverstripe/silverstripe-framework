<?php

namespace SilverStripe\ORM\Tests\ManyManyThroughListTest;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class FallbackLocale extends DataObject implements TestOnly
{
    private static $db = [
        'Sort' => 'Int',
    ];

    private static $has_one = [
        'Parent' => Locale::class,
        'Locale' => Locale::class,
    ];

    private static $table_name = 'ManyManyThroughTest_FallbackLocale';

    private static $default_sort = 'Sort';
}
