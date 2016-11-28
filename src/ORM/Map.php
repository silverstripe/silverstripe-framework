<?php

namespace SilverStripe\ORM;

use ArrayAccess;
use Countable;
use IteratorAggregate;

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
    protected $firstItems = array();

    /**
     * @see Map::push()
     *
     * @var array $lastItems
     */
    protected $lastItems = array();

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
        $array = array();

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
        return array_keys($this->toArray());
    }

    /**
     * Return all the values of this map.
     *
     * @return array
     */
    public function values()
    {
        return array_values($this->toArray());
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
        $this->firstItems = array(
            $key => $value
        );

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

        $this->lastItems = array(
            $key => $value
        );

        if ($oldItems) {
            $this->lastItems = $this->lastItems + $oldItems;
        }

        return $this;
    }

    // ArrayAccess

    /**
     * @var string $key
     *
     * @return boolean
     */
    public function offsetExists($key)
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

    /**
     * @var string $key
     *
     * @return mixed
     */
    public function offsetGet($key)
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
     * @var string $key
     * @var mixed $value
     */
    public function offsetSet($key, $value)
    {
        if (isset($this->firstItems[$key])) {
            $this->firstItems[$key] = $value;
        }

        if (isset($this->lastItems[$key])) {
            $this->lastItems[$key] = $value;
        }

        user_error(
            'Map is read-only. Please use $map->push($key, $value) to append values',
            E_USER_ERROR
        );
    }

    /**
     * Removes a value in the map by a given key which has been added to the map
     * via {@link Map::push()} or {@link Map::unshift()}
     *
     * Keys in the map cannot be unset since these values are derived from a
     * {@link DataQuery} instance. In this case, use {@link Map::toArray()}
     * and manipulate the resulting array.
     *
     * @var string $key
     * @var mixed $value
     */
    public function offsetUnset($key)
    {
        if (isset($this->firstItems[$key])) {
            unset($this->firstItems[$key]);

            return;
        }

        if (isset($this->lastItems[$key])) {
            unset($this->lastItems[$key]);

            return;
        }

        user_error(
            "Map is read-only. Unset cannot be called on keys derived from the DataQuery",
            E_USER_ERROR
        );
    }

    /**
     * Returns an Map_Iterator instance for iterating over the complete set
     * of items in the map.
     *
     * Satisfies the IteratorAggreagte interface.
     *
     * @return Map_Iterator
     */
    public function getIterator()
    {
        return new Map_Iterator(
            $this->list->getIterator(),
            $this->keyField,
            $this->valueField,
            $this->firstItems,
            $this->lastItems
        );
    }

    /**
     * Returns the count of items in the list including the additional items set
     * through {@link Map::push()} and {@link Map::unshift}.
     *
     * @return int
     */
    public function count()
    {
        return $this->list->count() +
            count($this->firstItems) +
            count($this->lastItems);
    }
}
