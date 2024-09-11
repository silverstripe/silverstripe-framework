<?php

namespace SilverStripe\Model\Tests\ModelDataTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\ModelData;

class Caster extends ModelData implements TestOnly
{

    public function forTemplate(): string
    {
        return 'casted';
    }

    public function setValue()
    {
    }
}
