<?php

namespace SilverStripe\Core\Tests\ClassInfoTest;

use SilverStripe\Core\CustomMethods;
use SilverStripe\Dev\TestOnly;

/**
 * Example of class with hasMethod() implementation
 */
class HasMethod implements TestOnly
{
    use CustomMethods;

    public function example()
    {
        return true;
    }
}
