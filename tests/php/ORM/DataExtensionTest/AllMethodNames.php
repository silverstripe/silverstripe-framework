<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Extension;

class AllMethodNames extends Extension implements TestOnly
{
    public function allMethodNames()
    {
        return [
            strtolower('getTestValueWith_' . ClassInfo::shortName($this->owner))
        ];
    }
}
