<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class AppliedToDO extends DataExtension implements TestOnly
{

    public function testMethodApplied()
    {
        return "hello world";
    }
}
