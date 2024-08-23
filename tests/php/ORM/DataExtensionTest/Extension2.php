<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Extension;

class Extension2 extends Extension implements TestOnly
{

    protected function canOne($member = null)
    {
        return true;
    }

    protected function canTwo($member = null)
    {
        return true;
    }

    protected function canThree($member = null)
    {
    }
}
