<?php

namespace SilverStripe\Model\Tests\ModelDataTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\ModelData;

class RequiresCasting extends ModelData implements TestOnly
{

    public $test = 'overwritten';

    public function forTemplate(): string
    {
        return 'casted';
    }

    public function setValue()
    {
    }
}
