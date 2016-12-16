<?php

namespace SilverStripe\Core\Tests\ObjectTest;

use SilverStripe\Core\Object;

class CacheTest extends Object
{

    public $count = 0;

    public function cacheMethod($arg1 = null)
    {
        return ($arg1) ? 'hasarg' : 'noarg';
    }

    public function incNumber()
    {
        $this->count++;
        return $this->count;
    }
}
