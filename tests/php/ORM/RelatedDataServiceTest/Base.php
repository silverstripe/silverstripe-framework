<?php

namespace SilverStripe\ORM\Tests\RelatedDataServiceTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Base extends DataObject implements TestOnly
{
    private static $table_name = 'TestOnly_RelatedDataServiceTest_Base';

    private static $db = [
        'Title' => 'Varchar'
    ];
}
