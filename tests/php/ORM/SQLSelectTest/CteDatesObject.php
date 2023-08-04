<?php

namespace SilverStripe\ORM\Tests\SQLSelectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class CteDatesObject extends DataObject implements TestOnly
{
    private static $table_name = 'SQLSelectTestCteDates';

    private static $db = [
        'Date' => 'Date',
        'Price' => 'Int',
    ];
}
