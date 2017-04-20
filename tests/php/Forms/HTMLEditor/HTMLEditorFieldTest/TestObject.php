<?php

namespace SilverStripe\Forms\Tests\HTMLEditor\HTMLEditorFieldTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'HTMLEditorFieldTest_TestObject';

    private static $db = array(
        'Title' => 'Varchar',
        'Content' => 'HTMLText',
        'HasBrokenFile' => 'Boolean'
    );
}
