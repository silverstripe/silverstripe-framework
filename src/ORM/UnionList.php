<?php

namespace SilverStripe\ORM;

use SilverStripe\Dev\Debug;
use SilverStripe\View\ViewableData;
use LogicException;

/**
 * A list object that wraps around other SS_List instances.
 */
class UnionList extends ViewableData implements SS_List
{
    protected $lists = array();

    public function __construct(array $lists = array())
    {
        $this->lists = array_values($lists);
        parent::__construct();
    }

    public function count()
    {
        $count = 0;
        foreach ($this->lists as $list) {
            $count += $list->count();
        }
        return $count;
    }

    public function exists()
    {
        return $this->count() > 0;
    }

    public function getIterator()
    {
        foreach ($this->lists as $i => $list) {
            foreach ($list as $record) {
                yield $record;
            }
        }
    }

    public function toArray()
    {
        $result = array();
        foreach ($this->lists as $i => $list) {
            foreach ($list as $record) {
                $result[] = $record;
            }
        }
        return $result;
    }

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

    public function toNestedArray()
    {
        $result = array();
        foreach ($this->lists as $i => $list) {
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

    public function first()
    {
        $list = isset($this->lists[0]) ? $this->lists[0] : null;
        return ($list) ? $list->first() : null;
    }

    public function last()
    {
        $list = end($this->lists);
        return ($list) ? $list->last() : null;
    }

    public function column($colName = "ID")
    {
        $result = array();
        foreach ($this->lists as $i => $list) {
            $result = array_merge($result, $list->column($colName));
        }
        return $result;
    }

    public function map($keyfield = 'ID', $titlefield = 'Title')
    {
        throw new LogicException(
            "UnionList::".__FUNCTION__."() is not allowed."
        );
    }

    public function find($key, $value)
    {
        throw new LogicException(
            "UnionList::".__FUNCTION__."() is not allowed."
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
