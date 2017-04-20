<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;

class ObjectG extends ObjectC implements TestOnly
{
    private static $table_name = 'DataQueryTest_G';

    private static $belongs_many_many = array(
        'ManyTestEs' => ObjectE::class,
    );
}
