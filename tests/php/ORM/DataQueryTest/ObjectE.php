<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;

class ObjectE extends ObjectC implements TestOnly
{
    private static $table_name = 'DataQueryTest_E';

    private static $db = array(
        'SortOrder' => 'Int'
    );

    private static $many_many = array(
        'ManyTestGs' => ObjectG::class,
    );

    private static $default_sort = '"DataQueryTest_E"."SortOrder" ASC';
}
