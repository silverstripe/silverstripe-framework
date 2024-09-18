<?php

namespace SilverStripe\Core\Tests\ObjectTest;

class ExtendTestBase extends BaseObject
{
    private static $extensions = [
        ExtendTest1::class,
        ExtendTest2::class,
    ];

    public function extendableMethod(&$argument = null, &$argument2 = null)
    {
        $args = implode(',', array_filter(func_get_args()));
        if ($argument2) {
            $argument2 = 'objectmodified';
        }
        return "ExtendTestBase($args)";
    }
}
