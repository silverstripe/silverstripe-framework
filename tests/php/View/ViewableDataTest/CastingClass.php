<?php

namespace SilverStripe\View\Tests\ViewableDataTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\ViewableData;

class CastingClass extends ViewableData implements TestOnly
{
    private static $casting = array(
        'Field' => 'CastingType',
        'Argument' => 'ArgumentType(Argument)',
        'ArrayArgument' => 'ArrayArgumentType(array(foo, bar))'
    );
}
