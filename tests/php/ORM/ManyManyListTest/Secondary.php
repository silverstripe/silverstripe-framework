<?php

namespace SilverStripe\ORM\Tests\ManyManyListTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

/**
 * A data object that implements the secondary side of a many_many when extended by
 * ManyManyListTest_IndirectSecondaryExtension.
 */
class Secondary extends DataObject implements TestOnly
{
    private static $table_name = 'ManyManyListTest_Secondary';

    // Possibly not required, but want to simulate a real test failure case where
    // database tables are present.
    private static $db = array(
        'Title' => 'Varchar(255)'
    );
}
