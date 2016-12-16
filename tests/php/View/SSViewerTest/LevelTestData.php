<?php

namespace SilverStripe\View\Tests\SSViewerTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ViewableData;

class LevelTestData extends ViewableData implements TestOnly
{
    protected $depth;

    public function __construct($depth = 1)
    {
        parent::__construct();
        $this->depth = $depth;
    }

    public function output($val)
    {
        return "$this->depth-$val";
    }

    public function forLoop($number)
    {
        $ret = array();
        for ($i = 0; $i < (int)$number; ++$i) {
            $ret[] = new TestObject("!$i");
        }
        return new ArrayList($ret);
    }

    public function forWith($number)
    {
        return new self($number);
    }
}
