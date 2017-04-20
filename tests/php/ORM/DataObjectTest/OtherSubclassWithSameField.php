<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;

class OtherSubclassWithSameField extends Team implements TestOnly
{
    private static $table_name = 'DataObjectTest_OtherSubclassWithSameField';

    private static $db = array(
        'SubclassDatabaseField' => 'Varchar',
    );
}
