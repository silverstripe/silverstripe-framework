<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class Extension2 extends DataExtension implements TestOnly
{

    public function canOne($member = null)
    {
        return true;
    }

    public function canTwo($member = null)
    {
        return true;
    }

    public function canThree($member = null)
    {
    }
}
