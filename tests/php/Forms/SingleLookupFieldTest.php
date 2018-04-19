<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Forms\SingleLookupField;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DropdownField;

/**
 * Class SingleLookupFieldTest
 *
 * @package SilverStripe\Forms\Tests
 */
class SingleLookupFieldTest extends SapphireTest
{
    public function testValueFromSource()
    {
        /** @var SingleLookupField $testField */
        $testField = DropdownField::create(
            'FirstName',
            'FirstName',
            ['member1' => 'Member 1', 'member2' => 'Member 2', 'member3' => 'Member 3']
        )->performReadonlyTransformation();

        $this->assertInstanceOf(SingleLookupField::class, $testField);

        $testField->setValue('member1');
        preg_match('/Member 1/', $testField->Field(), $matches);
        $this->assertEquals($matches[0], 'Member 1');
    }

    public function testValueNotFromSource()
    {
        /** @var SingleLookupField $testField */
        $testField = DropdownField::create(
            'FirstName',
            'FirstName',
            ['member1' => 'Member 1', 'member2' => 'Member 2', 'member3' => 'Member 3']
        )->performReadonlyTransformation();

        $this->assertInstanceOf(SingleLookupField::class, $testField);

        $testField->setValue('member123');
        preg_match('/\(none\)/', $testField->Field(), $matches);
        $this->assertEquals($matches[0], '(none)');
    }
}
