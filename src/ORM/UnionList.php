<?php

namespace SilverStripe\ORM;

use SilverStripe\Dev\Debug;
use SilverStripe\View\ViewableData;
use LogicException;

/**
 * A list object that wraps around other SS_List instances.
 * Allows iteration over multiple collections in one loop.
 */
class UnionList extends ViewableData implements SS_List
{
    /**
     * An array of SS_List instances
     *
     * @var array
     */
    protected $lists = array();

    /**
     * Create a new UnionList.
     *
     * @param array $lists - Lists to hold
     */
    public function __construct(array $lists)
    {
        foreach ($lists as $list) {
            $this->lists[] = clone $list;
        }
        parent::__construct();
    }

    /**
     * Return the total number of items in each SS_List.
     *
     * @return int
     */
    public function count()
    {
        $count = 0;
        foreach ($this->lists as $list) {
            $count += $list->count();
        }
        return $count;
    }

     /**
     * Returns true if one of the SS_List's have an item.
     *
     * @return bool
     */
    public function exists()
    {
        foreach ($this->lists as $list) {
            if ($list instanceof ArrayList) {
                if ($list->exists()) {
                    return true;
                }
            } elseif (method_exists($list, 'exists') && $list->exists()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Iterate over each SS_List, one after the other.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        foreach ($this->lists as $list) {
            foreach ($list as $record) {
                yield $record;
            }
        }
    }

    /**
     * Get array of each record in each SS_List.
     *
     * @return array
     */
    public function toArray()
    {
        $result = array();
        foreach ($this->lists as $list) {
            foreach ($list as $record) {
                $result[] = $record;
            }
        }
        return $result;
    }

    /**
     * Walks the list using the specified callback
     *
     * @param callable $callback
     * @return $this
     */
    public function each($callback)
    {
        foreach ($this as $item) {
            $callback($item);
        }
        return $this;
    }

    public function debug()
    {
        $val = "<h2>" . $this->class . "</h2><ul>";
        foreach ($this->toNestedArray() as $item) {
            $val .= "<li style=\"list-style-type: disc; margin-left: 20px\">" . Debug::text($item) . "</li>";
        }
        $val .= "</ul>";
        return $val;
    }

    /**
     * Return this list as an array and every object it as an sub array as well
     *
     * @return array
     */
    public function toNestedArray()
    {
        $result = array();
        foreach ($this->lists as $list) {
            foreach ($list as $item) {
                if (is_object($item)) {
                    if (method_exists($item, 'toMap')) {
                        $result[] = $item->toMap();
                    } else {
                        $result[] = (array) $item;
                    }
                } else {
                    $result[] = $item;
                }
            }
        }
        return $result;
    }

    /**
     * Get first item in the first SS_List.
     *
     * @return array
     */
    public function first()
    {
        $list = reset($this->lists);
        return $list ? $list->first() : null;
    }

    /**
     * Get last item in the last SS_List.
     *
     * @return array
     */
    public function last()
    {
        $list = end($this->lists);
        return $list ? $list->last() : null;
    }

     /**
     * Returns an array of a single field value for each item in each list.
     *
     * @param string $colName
     * @return array
     */
    public function column($colName = 'ID')
    {
        $result = array();
        foreach ($this->lists as $list) {
            $result = array_merge($result, $list->column($colName));
        }
        return $result;
    }

    /**
     * Returns a map of this list
     *
     * @param string $keyfield The 'key' field of the result array
     * @param string $titlefield The value field of the result array
     * @return Map
     */
    public function map($keyfield = 'ID', $titlefield = 'Title')
    {
        return new Map($this, $keyfield, $titlefield);
    }

    /**
     * Find the first item of this list where the given key = value
     *
     * @param string $key
     * @param string $value
     * @return mixed
     */
    public function find($key, $value)
    {
        foreach ($this->lists as $list) {
            $result = $list->find($key, $value);
            if ($result) {
                return $result;
            }
        }
        return null;
    }

    public function filter()
    {
        throw new LogicException(
            "UnionList::".__FUNCTION__."() is not allowed. This is due to API differences between DataList::filter() and ArrayList::filter()."
        );
    }

    public function add($item)
    {
        throw new LogicException(
            "UnionList::".__FUNCTION__."() is not allowed."
        );
    }

    public function remove($item)
    {
        throw new LogicException(
            "UnionList::".__FUNCTION__."() is not allowed."
        );
    }

    public function offsetExists($offset)
    {
        throw new LogicException(
            "UnionList::".__FUNCTION__."() is not allowed."
        );
    }

    public function offsetGet($offset)
    {
        throw new LogicException(
            "UnionList::".__FUNCTION__."() is not allowed."
        );
    }

    public function offsetSet($offset, $value)
    {
        throw new LogicException(
            "UnionList::".__FUNCTION__."() is not allowed."
        );
    }

    public function offsetUnset($offset)
    {
        throw new LogicException(
            "UnionList::".__FUNCTION__."() is not allowed."
        );
    }
}
