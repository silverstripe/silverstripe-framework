<?php

namespace SilverStripe\Core\Tests\ObjectTest;

class ExtendTest extends BaseObject
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
