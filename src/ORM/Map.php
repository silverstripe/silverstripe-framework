<?php

namespace SilverStripe\ORM;

use ArrayAccess;
use BadMethodCallException;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Creates a map from an SS_List by defining a key column and a value column.
 */
class Map implements ArrayAccess, Countable, IteratorAggregate
{

    protected $list, $keyField, $valueField;

    /**
     * @see Map::unshift()
     *
     * @var array $firstItems
     */
    protected $firstItems = [];

    /**
     * @see Map::push()
     *
     * @var array $lastItems
     */
    protected $lastItems = [];

    /**
     * Construct a new map around an SS_list.
     *
     * @param SS_List $list The list to build a map from
     * @param string $keyField The field to use as the key of each map entry
     * @param string $valueField The field to use as the value of each map entry
     */
    public function __construct(SS_List $list, $keyField = "ID", $valueField = "Title")
    {
        $this->list = $list;
        $this->keyField = $keyField;
        $this->valueField = $valueField;
    }

    /**
     * Set the key field for this map.
     *
     * @var string $keyField
     */
    public function setKeyField($keyField)
    {
        $this->keyField = $keyField;
    }

    /**
     * Set the value field for this map.
     *
     * @var string $valueField
     */
    public function setValueField($valueField)
    {
        $this->valueField = $valueField;
    }

    /**
     * Return an array equivalent to this map.
     *
     * @return array
     */
    public function toArray()
    {
        $array = [];

        foreach ($this as $k => $v) {
            $array[$k] = $v;
        }

        return $array;
    }

    /**
     * Return all the keys of this map.
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->toArray() ?? []);
    }

    /**
     * Return all the values of this map.
     *
     * @return array
     */
    public function values()
    {
        return array_values($this->toArray() ?? []);
    }

    /**
     * Unshift an item onto the start of the map.
     *
     * Stores the value in addition to the {@link DataQuery} for the map.
     *
     * @var string $key
     * @var mixed $value
     * @return $this
     */
    public function unshift($key, $value)
    {
        $oldItems = $this->firstItems;
        $this->firstItems = [
            $key => $value
        ];

        if ($oldItems) {
            $this->firstItems = $this->firstItems + $oldItems;
        }

        return $this;
    }

    /**
     * Pushes an item onto the end of the map.
     *
     * @var string $key
     * @var mixed $value
     * @return $this
     */
    public function push($key, $value)
    {
        $oldItems = $this->lastItems;

        $this->lastItems = [
            $key => $value
        ];

        if ($oldItems) {
            $this->lastItems = $this->lastItems + $oldItems;
        }

        return $this;
    }

    public function offsetExists(mixed $key): bool
    {
        if (isset($this->firstItems[$key])) {
            return true;
        }

        if (isset($this->lastItems[$key])) {
            return true;
        }

        $record = $this->list->find($this->keyField, $key);

        return $record != null;
    }

    public function offsetGet(mixed $key): mixed
    {
        if (isset($this->firstItems[$key])) {
            return $this->firstItems[$key];
        }

        if (isset($this->lastItems[$key])) {
            return $this->lastItems[$key];
        }

        $record = $this->list->find($this->keyField, $key);

        if ($record) {
            $col = $this->valueField;

            return $record->$col;
        }

        return null;
    }

    /**
     * Sets a value in the map by a given key that has been set via
     * {@link Map::push()} or {@link Map::unshift()}
     *
     * Keys in the map cannot be set since these values are derived from a
     * {@link DataQuery} instance. In this case, use {@link Map::toArray()}
     * and manipulate the resulting array.
     *
     * @throws BadMethodCallException
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        if (isset($this->firstItems[$key])) {
            $this->firstItems[$key] = $value;
        }

        if (isset($this->lastItems[$key])) {
            $this->lastItems[$key] = $value;
        }

        throw new BadMethodCallException('Map is read-only. Please use $map->push($key, $value) to append values');
    }

    /**
     * Removes a value in the map by a given key which has been added to the map
     * via {@link Map::push()} or {@link Map::unshift()}
     *
     * Keys in the map cannot be unset since these values are derived from a
     * {@link DataQuery} instance. In this case, use {@link Map::toArray()}
     * and manipulate the resulting array.
     *
     * @throws BadMethodCallException
     */
    public function offsetUnset(mixed $key): void
    {
        if (isset($this->firstItems[$key])) {
            unset($this->firstItems[$key]);
            return;
        }

        if (isset($this->lastItems[$key])) {
            unset($this->lastItems[$key]);
            return;
        }

        throw new BadMethodCallException(
            'Map is read-only. Unset cannot be called on keys derived from the DataQuery.'
        );
    }

    /**
     * Returns an Map_Iterator instance for iterating over the complete set
     * of items in the map.
     */
    public function getIterator(): Traversable
    {
        $keyField = $this->keyField;
        $valueField = $this->valueField;

        foreach ($this->firstItems as $k => $v) {
            yield $k => $v;
        }

        foreach ($this->list as $record) {
            if (isset($this->firstItems[$record->$keyField])) {
                continue;
            }
            if (isset($this->lastItems[$record->$keyField])) {
                continue;
            }
            yield $this->extractValue($record, $this->keyField) => $this->extractValue($record, $this->valueField);
        }

        foreach ($this->lastItems as $k => $v) {
            yield $k => $v;
        }
    }

    /**
     * Extracts a value from an item in the list, where the item is either an
     * object or array.
     *
     * @param  array|object $item
     * @param  string $key
     * @return mixed
     */
    protected function extractValue($item, $key)
    {
        if (is_object($item)) {
            if (method_exists($item, 'hasMethod') && $item->hasMethod($key)) {
                return $item->{$key}();
            }
            return $item->{$key};
        } else {
            if (array_key_exists($key, $item)) {
                return $item[$key];
            }
        }
    }

    /**
     * Returns the count of items in the list including the additional items set
     * through {@link Map::push()} and {@link Map::unshift}.
     */
    public function count(): int
    {
        return $this->list->count() +
            count($this->firstItems ?? []) +
            count($this->lastItems ?? []);
    }
}
