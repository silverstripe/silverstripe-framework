<?php

namespace SilverStripe\Dev\Tests\DeprecationTest;

use SilverStripe\Dev\Deprecation;

class TestDeprecation extends Deprecation
{
    public static function get_module()
    {
        return self::get_calling_module_from_trace(debug_backtrace(0));
    }

    public static function get_method()
    {
        return self::get_called_method_from_trace(debug_backtrace(0));
    }
}
