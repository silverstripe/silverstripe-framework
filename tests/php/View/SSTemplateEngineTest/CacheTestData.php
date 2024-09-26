<?php

namespace SilverStripe\View\Tests\SSTemplateEngineTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\ModelData;

class CacheTestData extends ModelData implements TestOnly
{

    public $testWithCalls = 0;
    public $testLoopCalls = 0;

    public function TestWithCall()
    {
        $this->testWithCalls++;
        return ArrayData::create(['Message' => 'Hi']);
    }

    public function TestLoopCall()
    {
        $this->testLoopCalls++;
        return ArrayList::create(
            [
            ArrayData::create(['Message' => 'One']),
            ArrayData::create(['Message' => 'Two'])
            ]
        );
    }
}
