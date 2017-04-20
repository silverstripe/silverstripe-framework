<?php

namespace SilverStripe\Forms\Tests\NumericFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'NumericFieldTest_Object';

    private static $db = array(
        'Number' => 'Float'
    );
}
