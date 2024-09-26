<?php

namespace SilverStripe\View\Tests\ViewLayerDataTest;

use BadMethodCallException;
use SilverStripe\Dev\TestOnly;

/**
 * A test fixture that captures information about what's being fetched on it
 * Has explicit methods instead of relying on __call()
 */
class TestFixtureComplex implements TestOnly
{
    private array $requested = [];

    public function badMethodCall(): void
    {
        $this->requested[] = [
            'type' => 'method',
            'name' => __FUNCTION__,
            'args' => func_get_args(),
        ];
        throw new BadMethodCallException('Without a __call() method this will actually be thrown');
    }

    public function voidMethod(): void
    {
        $this->requested[] = [
            'type' => 'method',
            'name' => __FUNCTION__,
            'args' => func_get_args(),
        ];
    }

    public function justCallMethod(): string
    {
        $this->requested[] = [
            'type' => 'method',
            'name' => __FUNCTION__,
            'args' => func_get_args(),
        ];
        return 'This is a method value';
    }

    public function getActualValue(): string
    {
        $this->requested[] = [
            'type' => 'method',
            'name' => __FUNCTION__,
            'args' => func_get_args(),
        ];
        return 'this is the value';
    }

    public function __get(string $name): ?string
    {
        $this->requested[] = [
            'type' => 'property',
            'name' => $name,
        ];
        if ($name === 'ActualValueField') {
            return 'the value is here';
        }
        return null;
    }

    /**
     * We need this so we always try to fetch a property.
     */
    public function __isset(string $name): bool
    {
        return $name !== 'NotSet';
    }

    public function getRequested(): array
    {
        return $this->requested;
    }
}
