<?php

namespace SilverStripe\Forms\Tests\DatetimeFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Model extends DataObject implements TestOnly
{
    private static $table_name = 'DatetimeFieldTest_Model';

    private static $db = array(
        'MyDatetime' => 'Datetime'
    );
}
