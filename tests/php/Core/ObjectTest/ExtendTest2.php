<?php

namespace SilverStripe\Core\Tests\ObjectTest;

use SilverStripe\Core\Extension;

class ExtendTest2 extends Extension
{
    public function extendableMethod($argument = null)
    {
        $args = implode(',', array_filter(func_get_args()));
        return "ExtendTest2($args)";
    }
}
