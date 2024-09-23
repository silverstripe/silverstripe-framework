<?php

namespace SilverStripe\Dev\Tests\ModelDataContainsTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\ModelData;

class TestObject extends ModelData implements TestOnly
{
    protected $data = null;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function hasField(string $fieldName): bool
    {
        return isset($this->data[$fieldName]);
    }

    public function getField(string $fieldName): mixed
    {
        return isset($this->data[$fieldName]) ?: null;
    }

    public function getSomething()
    {
        return 'something';
    }
}
