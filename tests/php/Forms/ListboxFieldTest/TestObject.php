<?php

namespace SilverStripe\Forms\Tests\ListboxFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'ListboxFieldTest_DataObject';

    private static $db = array(
        'Choices' => 'Text'
    );
}
