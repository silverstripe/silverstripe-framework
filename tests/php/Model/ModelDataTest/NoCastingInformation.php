<?php

namespace SilverStripe\Model\Tests\ModelDataTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\ModelData;

class NoCastingInformation extends ModelData implements TestOnly
{
    public function noCastingInformation()
    {
        return "No casting information";
    }
}
