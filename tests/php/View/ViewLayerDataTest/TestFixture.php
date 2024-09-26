<?php

namespace SilverStripe\View\Tests\ViewLayerDataTest;

use BadMethodCallException;
use SilverStripe\Dev\TestOnly;

/**
 * A test fixture that captures information about what's being fetched on it
 */
class TestFixture implements TestOnly
{
    private array $requested = [];

    public bool $throwException = true;

    public function __call(string $name, array $arguments = []): null
    {
        $this->requested[] = [
            'type' => 'method',
            'name' => $name,
            'args' => $arguments,
        ];
        if ($this->throwException) {
            throw new BadMethodCallException('We need this so ViewLayerData will try the next step');
        } else {
            return null;
        }
    }

    public function __get(string $name): null
    {
        $this->requested[] = [
            'type' => 'property',
            'name' => $name,
        ];
        return null;
    }

    public function __isset(string $name): bool
    {
        return $name !== 'NotSet';
    }

    public function getRequested(): array
    {
        return $this->requested;
    }
}
