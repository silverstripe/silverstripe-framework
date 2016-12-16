<?php

namespace SilverStripe\Core\Tests\ObjectTest;

use SilverStripe\Core\Extension;

class ExtendTest1 extends Extension
{
    public function extendableMethod(&$argument = null)
    {
        if ($argument) {
            $argument = 'modified';
        }
        return null;
    }
}
