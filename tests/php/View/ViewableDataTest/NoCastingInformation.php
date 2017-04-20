<?php

namespace SilverStripe\View\Tests\ViewableDataTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\ViewableData;

class NoCastingInformation extends ViewableData implements TestOnly
{
    public function noCastingInformation()
    {
        return "No casting information";
    }
}
