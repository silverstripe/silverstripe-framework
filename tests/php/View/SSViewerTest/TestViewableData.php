<?php

namespace SilverStripe\View\Tests\SSViewerTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\ViewableData;

/**
 * @property string $TextValue
 * @property string $HTMLValue
 */
class TestViewableData extends ViewableData implements TestOnly
{

    private static $default_cast = 'Text';

    private static $casting = array(
        'TextValue' => 'Text',
        'HTMLValue' => 'HTMLFragment'
    );

    public function methodWithOneArgument($arg1)
    {
        return "arg1:{$arg1}";
    }

    public function methodWithTwoArguments($arg1, $arg2)
    {
        return "arg1:{$arg1},arg2:{$arg2}";
    }
}
