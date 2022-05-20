<?php

namespace SilverStripe\Dev\Tests\ViewableDataContainsTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\View\ViewableData;

class TestObject extends ViewableData implements TestOnly
{
    protected $data = null;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function hasField($name)
    {
        return isset($this->data[$name]);
    }

    public function getField($name)
    {
        return isset($this->data[$name]) ?: null;
    }

    public function getSomething()
    {
        return 'something';
    }
}
