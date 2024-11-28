<?php

namespace SilverStripe\View\Tests\ViewLayerDataTest;

use BadMethodCallException;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\ModelData;

class TestBadMethodCallObjectSubclass extends TestBadMethodCallObject implements TestOnly
{
    public function __call(string $name, array $arguments): void
    {
        if ($name === 'directMethod') {
            throw new BadMethodCallException('This exception should be caught by ViewLayerData');
        }
        if ($name === 'realMethod') {
            $this->throwException();
        }
        if ($name === 'anotherClass') {
            $data = new ModelData();
            $data->thisMethodDoesntExist();
        }
        parent::__call($name, $arguments);
    }

    public function throwException(): void
    {
        throw new BadMethodCallException('This exception should NOT be caught by ViewLayerData');
    }
}
