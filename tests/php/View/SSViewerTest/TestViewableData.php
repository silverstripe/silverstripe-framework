<?php

namespace SilverStripe\View\Tests\SSViewerTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\ModelData;

/**
 * @property string $TextValue
 * @property string $HTMLValue
 */
class TestModelData extends ModelData implements TestOnly
{

    private static $default_cast = 'Text';

    private static $casting = [
        'TextValue' => 'Text',
        'HTMLValue' => 'HTMLFragment'
    ];

    public function methodWithOneArgument($arg1)
    {
        return "arg1:{$arg1}";
    }

    public function methodWithTwoArguments($arg1, $arg2)
    {
        return "arg1:{$arg1},arg2:{$arg2}";
    }

    public function methodWithTypedArguments($arg1, $arg2, $arg3)
    {
        return 'arg1:' . json_encode($arg1) . ',arg2:' . json_encode($arg2) . ',arg3:' . json_encode($arg3);
    }

    public function Type($arg)
    {
        return gettype($arg) . ':' . $arg;
    }
}
