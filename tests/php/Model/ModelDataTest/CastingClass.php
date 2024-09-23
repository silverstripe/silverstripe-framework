<?php

namespace SilverStripe\Model\Tests\ModelDataTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\ModelData;

class CastingClass extends ModelData implements TestOnly
{
    private static $casting = [
        'Field' => 'CastingType',
        'Argument' => 'ArgumentType(Argument)',
        'ArrayArgument' => 'ArrayArgumentType([foo, bar])'
    ];
}
