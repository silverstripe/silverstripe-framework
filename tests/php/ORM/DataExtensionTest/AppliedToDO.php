<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Extension;

class AppliedToDO extends Extension implements TestOnly
{

    public function testMethodApplied()
    {
        return "hello world";
    }
}
