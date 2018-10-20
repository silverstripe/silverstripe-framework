<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\CheckboxField_Readonly;

class CheckboxField_ReadonlyTest extends SapphireTest
{
    public function testPerformReadonlyTransformation()
    {
        $field = new CheckboxField_Readonly('Test');
        $result = $field->performReadonlyTransformation();
        $this->assertInstanceOf(CheckboxField_Readonly::class, $result);
        $this->assertNotSame($result, $field);
    }
}
