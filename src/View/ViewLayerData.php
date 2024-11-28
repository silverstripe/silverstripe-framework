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

/**
 * This class is used to wrap data before it gets sent to the view layer.
 *
 * Wrapping data in this class ensures it has consistent escaping and casting applied
 * to it, regardless of what type the original data is.
 */
class ViewLayerData implements IteratorAggregate, Stringable
{
    use Injectable;

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

    /**
     * @param mixed $source The source of the data
     * @param string $name The name of the field the data comes from, if known
     */
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
     * Needed so we can rewind in ScopeManager::next() after getting itemIteratorTotal without throwing an exception.
     */
    public function getIteratorCount(): int
    {
        $count = $this->getRawDataValue('count');
        if (is_numeric($count)) {
            return (int) $count;
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

        $iterable = $this->data;
        if (!is_iterable($iterable)) {
            $iterable = $this->data->getIterator();
        }
        $source = $this->data instanceof ModelData ? $this->data : null;
        foreach ($iterable as $item) {
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
        $value = $this->getRawDataValue($name);
        if ($value === null) {
            return null;
        }
        $source = $this->data instanceof ModelData ? $this->data : null;
        return ViewLayerData::create($value, $source, $name);
    }

    public function __call(string $name, array $arguments = []): ?ViewLayerData
    {
        $value = $this->getRawDataValue($name, $arguments);
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
     * If $name is passed, we check for a value in the property/method with that name. Otherwise,
     * check if the currently scoped data has a value.
     */
    public function hasDataValue(?string $name = null, array $arguments = []): bool
    {
        if ($name) {
            // Ask the model if it has a value for that field
            if ($this->data instanceof ModelData) {
                return $this->data->hasValue($name, $arguments);
            }
            // Check for ourselves if there's a value for that field
            // This mimics what ModelData does, which provides consistency
            $value = $this->getRawDataValue($name, $arguments);
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
    public function getRawDataValue(string $name, array $arguments = []): mixed
    {
        $data = $this->data;
        if ($data instanceof ModelDataCustomised && $data->customisedHas($name)) {
            $data = $data->getCustomisedModelData();
        }

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
        $valueWasFetched = false;

        // Try calling a method even if we're fetching as a property
        // This matches historical behaviour that a LOT of logic in core modules expects
        $value = $this->callDataMethod($data, $name, $arguments, $valueWasFetched);

        // Try to get a property even if we aren't explicitly trying to call a method, if the method didn't exist.
        // This matches historical behaviour and allows e.g. `$MyProperty(some-arg)` with a `getMyProperty($arg)` method.
        if (!$valueWasFetched) {
            // Try an explicit getter
            // This matches the "magic" getter behaviour of ModelData across the board for consistent results
            $getter = "get{$name}";
            $value = $this->callDataMethod($data, $getter, $arguments, $valueWasFetched);
            if (!$valueWasFetched && isset($data->$name)) {
                $value = $data->$name;
                $valueWasFetched = true;
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

    private function callDataMethod(object $data, string $name, array $arguments, bool &$valueWasFetched = false): mixed
    {
        $hasDynamicMethods = method_exists($data, '__call');
        $hasMethod = ClassInfo::hasMethod($data, $name);
        if ($hasMethod || $hasDynamicMethods) {
            try {
                $value = $data->$name(...$arguments);
                $valueWasFetched = true;
                return $value;
            } catch (BadMethodCallException $e) {
                // Only throw the exception if we weren't relying on __call
                // It's common for __call to throw BadMethodCallException for methods that aren't "implemented"
                // so we just want to return null in those cases - but only if it's a direct result of our method call.
                if (!$hasDynamicMethods || $this->mustThrow($e->getTrace())) {
                    throw $e;
                }
            }
        }
        return null;
    }

    private function mustThrow(array $trace): bool
    {
        $dataClass = get_class($this->data);
        foreach ($trace as $data) {
            $class = $data['class'] ?? '';
            $method = $data['function'] ?? '';
            if ($class === ViewLayerData::class) {
                // If we hit ViewLayerData::callDataMethod() we've finished checking the relevant parts of the stack
                if ($method === 'callDataMethod') {
                    break;
                }
                // If we're trying to call some other method on ViewLayerData and it causes problems, throw the exception.
                return true;
            }
            // If we find a non __call method return true
            // This means our method exists, but it tried to call something else which doesn't
            if ($method !== '__call') {
                return true;
            }
            // If we break class inheritance return true
            if (!is_a($class, $dataClass, true) && !is_a($dataClass, $class, true)) {
                return true;
            }
        }
        // Hitting this means `callDataMethod()` only hit `__call()` methods inside the class hierarchy of our data, which
        // means the method we were actually trying to call is missing and we can safely ignore the exception.
        return false;
    }
}
