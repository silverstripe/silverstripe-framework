<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBInt;

class DBIntTest extends SapphireTest
{
    public function testGetValueCastToInt()
    {
        $field = DBInt::create('MyField');
        $field->setValue(3);
        $this->assertSame(3, $field->getValue());
        $field->setValue('3');
        $this->assertSame(3, $field->getValue());
    }
}
