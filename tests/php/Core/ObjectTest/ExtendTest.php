<?php

namespace SilverStripe\Core\Tests\ObjectTest;

use SilverStripe\Core\Object;

class ExtendTest extends Object
{
    private static $extensions = array(
        ExtendTest1::class,
        ExtendTest2::class,
    );

    public function extendableMethod(&$argument = null, &$argument2 = null)
    {
        $args = implode(',', array_filter(func_get_args()));
        if ($argument2) {
            $argument2 = 'objectmodified';
        }
        return "ExtendTest($args)";
    }
}
