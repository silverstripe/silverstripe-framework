<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DisabledTransformation;
use SilverStripe\Forms\TextField;

class DisabledTransformationTest extends SapphireTest
{
    public function testTransform()
    {
        $field = new TextField('Test');

        $transformation = new DisabledTransformation();
        $newField = $transformation->transform($field);

        $this->assertTrue($newField->isDisabled(), 'Transformation failed to transform field to be disabled');
    }
}
