<?php

namespace SilverStripe\View;

use BadMethodCallException;
use InvalidArgumentException;
use IteratorAggregate;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Model\ModelData;
use SilverStripe\Model\ModelDataCustomised;
use SilverStripe\ORM\FieldType\DBClassName;
use Stringable;
use Traversable;

class ViewLayerData implements IteratorAggregate, Stringable
{
    use Injectable;

    public const TYPE_PROPERTY = 'property';

    public const TYPE_METHOD = 'method';

    public const TYPE_ANY = 'any';

    /**
     * Special variable names that can be used to get metadata about values
     */
    public const META_DATA_NAMES = [
        // Gets a DBClassName with the class name of $this->data
        'ClassName',
        // Returns $this->data
        'Me',
    ];

    private object $data;

    public function __construct(mixed $data, mixed $source = null, string $name = '')
    {
        if ($data === null) {
            throw new InvalidArgumentException('$data must not be null');
        }
        if ($data instanceof ViewLayerData) {
            $data = $data->data;
        } else {
            $source = $source instanceof ModelData ? $source : null;
            $data = CastingService::singleton()->cast($data, $source, $name);
        }
        $this->data = $data;
    }

    /**
     * Needed so we can rewind in SSViewer_Scope::next() after getting itemIteratorTotal without throwing an exception.
     */
    public function getIteratorCount(): int
    {
        $count = $this->getRawDataValue('count');
        if (is_numeric($count)) {
            return $count;
        }
        if (is_countable($this->data)) {
            return count($this->data);
        }
        if (ClassInfo::hasMethod($this->data, 'getIterator')) {
            return iterator_count($this->data->getIterator());
        }
        return 0;
    }

    public function getIterator(): Traversable
    {
        if (!is_iterable($this->data) && !ClassInfo::hasMethod($this->data, 'getIterator')) {
            $type = get_class($this->data);
            throw new BadMethodCallException("$type is not iterable.");
        }

        $iterator = $this->data;
        if (!is_iterable($iterator)) {
            $iterator = $this->data->getIterator();
        }
        $source = $this->data instanceof ModelData ? $this->data : null;
        foreach ($iterator as $item) {
            yield $item === null ? null : ViewLayerData::create($item, $source);
        }
    }

    /**
     * Checks if a field is set, or if a getter or a method of that name exists.
     * We need to check each of these, because we don't currently distinguish between a property, a getter, and a method
     * which means if any of those exists we have to say the field is "set", otherwise template engines may skip fetching the data.
     */
    public function __isset(string $name): bool
    {
        // Note we explicitly DO NOT call count() or exists() on the data here because that would
        // require fetching the data prematurely which could cause performance issues in extreme cases
        return in_array($name, ViewLayerData::META_DATA_NAMES)
            || isset($this->data->$name)
            || ClassInfo::hasMethod($this->data, "get$name")
            || ClassInfo::hasMethod($this->data, $name);
    }

    public function __get(string $name): ?ViewLayerData
    {
        $value = $this->getRawDataValue($name, type: ViewLayerData::TYPE_PROPERTY);
        if ($value === null) {
            return null;
        }
        $source = $this->data instanceof ModelData ? $this->data : null;
        return ViewLayerData::create($value, $source, $name);
    }

    public function __call(string $name, array $arguments = []): ?ViewLayerData
    {
        $value = $this->getRawDataValue($name, $arguments, ViewLayerData::TYPE_METHOD);
        if ($value === null) {
            return null;
        }
        $source = $this->data instanceof ModelData ? $this->data : null;
        return ViewLayerData::create($value, $source, $name);
    }

    public function __toString(): string
    {
        if (ClassInfo::hasMethod($this->data, 'forTemplate')) {
            return $this->data->forTemplate();
        }
        return (string) $this->data;
    }

    /**
     * Check if there is a truthy value or (for ModelData) if the data exists().
     */
    public function hasDataValue(?string $name = null, array $arguments = [], string $type = ViewLayerData::TYPE_ANY): bool
    {
        if ($name) {
            // Ask the model if it has a value for that field
            if ($this->data instanceof ModelData) {
                return $this->data->hasValue($name, $arguments);
            }
            // Check for ourselves if there's a value for that field
            // This mimics what ModelData does, which provides consistency
            $value = $this->getRawDataValue($name, $arguments, $type);
            if ($value === null) {
                return false;
            }
            $source = $this->data instanceof ModelData ? $this->data : null;
            return ViewLayerData::create($value, $source, $name)->hasDataValue();
        }
        // Ask the model if it "exists"
        if ($this->data instanceof ModelData) {
            return $this->data->exists();
        }
        // Mimics ModelData checks on lists
        if (is_countable($this->data)) {
            return count($this->data) > 0;
        }
        // Check for truthiness (which is effectively `return true` since data is an object)
        // We do this to mimic ModelData->hasValue() for consistency
        return (bool) $this->data;
    }

    /**
     * Get the raw value of some field/property/method on the data, without wrapping it in ViewLayerData.
     */
    public function getRawDataValue(string $name, array $arguments = [], string $type = ViewLayerData::TYPE_ANY): mixed
    {
        if ($type !== ViewLayerData::TYPE_ANY && $type !== ViewLayerData::TYPE_METHOD && $type !== ViewLayerData::TYPE_PROPERTY) {
            throw new InvalidArgumentException('$type must be one of the TYPE_* constant values');
        }

        $data = $this->data;
        if ($data instanceof ModelDataCustomised && $data->customisedHas($name)) {
            $data = $data->getCustomisedModelData();
        }

        // We don't currently use the $type, but could in a future enhancement if we find that distinction useful.
        $value = $this->getValueFromData($data, $name, $arguments);

        return $value;
    }

    private function getValueFromData(object $data, string $name, array $arguments): mixed
    {
        // Values from ModelData can be cached
        if ($data instanceof ModelData) {
            $cached = $data->objCacheGet($name, $arguments);
            if ($cached !== null) {
                return $cached;
            }
        }

        $value = null;
        // Keep track of whether we've already fetched a value (allowing null to be the correct value)
        $fetchedValue = false;

        // Try calling a method even if we're fetching as a property
        // This matches historical behaviour that a LOT of logic in core modules expects
        $value = $this->callDataMethod($data, $name, $arguments, $fetchedValue);

        // Try to get a property even if we aren't explicitly trying to call a method, if the method didn't exist.
        // This matches historical behaviour and allows e.g. `$MyProperty(some-arg)` with a `getMyProperty($arg)` method.
        if (!$fetchedValue) {
            // Try an explicit getter
            // This matches the "magic" getter behaviour of ModelData across the board for consistent results
            $getter = "get{$name}";
            $value = $this->callDataMethod($data, $getter, $arguments, $fetchedValue);
            if (!$fetchedValue && isset($data->$name)) {
                $value = $data->$name;
                $fetchedValue = true;
            }
        }

        // Caching for modeldata
        if ($data instanceof ModelData) {
            $data->objCacheSet($name, $arguments, $value);
        }

        if ($value === null && in_array($name, ViewLayerData::META_DATA_NAMES)) {
            $value = $this->getMetaData($data, $name);
        }

        return $value;
    }

    private function getMetaData(object $data, string $name): mixed
    {
        return match ($name) {
            'Me' => $data,
            'ClassName' => DBClassName::create()->setValue(get_class($data)),
            default => null
        };
    }

    private function callDataMethod(object $data, string $name, array $arguments, bool &$fetchedValue = false): mixed
    {
        $hasDynamicMethods = method_exists($data, '__call');
        $hasMethod = ClassInfo::hasMethod($data, $name);
        if ($hasMethod || $hasDynamicMethods) {
            try {
                $value = $data->$name(...$arguments);
                $fetchedValue = true;
                return $value;
            } catch (BadMethodCallException $e) {
                // Only throw the exception if we weren't relying on __call
                // It's common for __call to throw BadMethodCallException for methods that aren't "implemented"
                // so we just want to return null in those cases.
                if (!$hasDynamicMethods) {
                    throw $e;
                }
            }
        }
        return null;
    }
}
