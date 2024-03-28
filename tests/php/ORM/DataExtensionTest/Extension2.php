<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class Extension2 extends DataExtension implements TestOnly
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
