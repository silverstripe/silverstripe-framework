<?php

namespace SilverStripe\ORM\Tests\DataQueryTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class ObjectD extends DataObject implements TestOnly
{
    private static $table_name = 'DataQueryTest_D';

    private static $has_one = array(
        'Relation' => ObjectB::class,
    );
}
