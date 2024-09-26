<?php

namespace SilverStripe\View\Tests\SSTemplateEngineTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\ModelData;

class LevelTestData extends ModelData implements TestOnly
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
        $ret = [];
        for ($i = 0; $i < (int)$number; ++$i) {
            $ret[] = new TestObject("!$i");
        }
        return new ArrayList($ret);
    }

    public function forWith($number)
    {
        return new LevelTestData($number);
    }
}
