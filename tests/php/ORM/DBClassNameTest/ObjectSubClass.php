<?php

namespace SilverStripe\ORM\Tests\DBClassNameTest;

class ObjectSubClass extends TestObject
{
    private static $table_name = 'DBClassNameTest_ObjectSubClass';

    private static $db = array(
        'MidClassDefault' => 'DBClassName',
        'MidClass' => 'DBClassName(\'SilverStripe\\ORM\\Tests\\DBClassNameTest\\ObjectSubclass\')'
    );
}
