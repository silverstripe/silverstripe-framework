<?php

namespace SilverStripe\Model\Tests\ModelDataTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Model\ModelData;

/**
 * A model that captures information about what's being fetched on it for some methods
 */
class TestModelData extends ModelData implements TestOnly
{
    private array $requested = [];

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

    public function getField(string $name): ?string
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
    public function hasField(string $name): bool
    {
        return $name !== 'NotSet';
    }

    public function getRequested(): array
    {
        return $this->requested;
    }
}
