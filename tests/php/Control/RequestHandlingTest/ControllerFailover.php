<?php

namespace SilverStripe\Control\Tests\RequestHandlingTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\ModelData;

class ControllerFailover extends ModelData implements TestOnly
{
    public function failoverMethod()
    {
        return "failoverMethod";
    }
}
