<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Tests\DBClassNameTest;

use SilverStripe\ORM\Tests\DBClassNameTest\ObjectSubClass;

class ObjectSubSubClass extends ObjectSubClass
{
    private static $table_name = 'DBClassNameTest_ObjectSubSubClass';
}
