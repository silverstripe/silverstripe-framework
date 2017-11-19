<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBEnum;
use SilverStripe\ORM\FieldType\DBField;

class DBEnumTest extends SapphireTest
{
    public function testDefault()
    {
        /** @var DBEnum $enum1 */
        $enum1 = DBField::create_field('Enum("A, B, C, D")', null);
        /** @var DBEnum $enum2 */
        $enum2 = DBField::create_field('Enum("A, B, C, D", "")', null);
        /** @var DBEnum $enum3 */
        $enum3 = DBField::create_field('Enum("A, B, C, D", null)', null);
        /** @var DBEnum $enum4 */
        $enum4 = DBField::create_field('Enum("A, B, C, D", 1)', null);

        $this->assertEquals('A', $enum1->getDefaultValue());
        $this->assertEquals('A', $enum1->getDefault());
        $this->assertEquals(null, $enum2->getDefaultValue());
        $this->assertEquals(null, $enum2->getDefault());
        $this->assertEquals(null, $enum3->getDefaultValue());
        $this->assertEquals(null, $enum3->getDefault());
        $this->assertEquals('B', $enum4->getDefaultValue());
        $this->assertEquals('B', $enum4->getDefault());
    }
}
