<?php

namespace SilverStripe\ORM\Tests\DBClassNameTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
    private static $table_name = 'DBClassNameTest_Object';

    private static $db = array(
        'DefaultClass' => 'DBClassName',
        'AnyClass' => 'DBClassName(\'SilverStripe\\ORM\\DataObject\')',
        'ChildClass' => 'DBClassName(\'SilverStripe\\ORM\\Tests\\DBClassNameTest\\ObjectSubClass\')',
        'LeafClass' => 'DBClassName(\'SilverStripe\\ORM\\Tests\\DBClassNameTest\\ObjectSubSubClass\')'
    );
}
