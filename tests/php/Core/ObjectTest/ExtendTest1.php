<?php

namespace SilverStripe\Core\Tests\ObjectTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

class ExtendTest1 extends Extension implements TestOnly
{
    protected function extendableMethod(&$argument = null)
    {
        if ($argument) {
            $argument = 'modified';
        }
        return null;
    }
}
