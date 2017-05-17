<?php

namespace SilverStripe\Core\Tests\ObjectTest;

class CacheTest extends BaseObject
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
