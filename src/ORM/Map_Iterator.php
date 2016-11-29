<?php

namespace SilverStripe\ORM;

use Iterator;

/**
 * Builds a map iterator around an Iterator.  Called by Map
 */
class Map_Iterator implements Iterator
{

    /**
     * @var Iterator
     **/
    protected $items;

    protected $keyField, $titleField;

    protected $firstItemIdx = 0;

    protected $endItemIdx;

    protected $firstItems = array();
    protected $lastItems = array();

    protected $excludedItems = array();

    /**
     * @param Iterator $items The iterator to build this map from
     * @param string $keyField The field to use for the keys
     * @param string $titleField The field to use for the values
     * @param array $firstItems An optional map of items to show first
     * @param array $lastItems An optional map of items to show last
     */
    public function __construct(Iterator $items, $keyField, $titleField, $firstItems = null, $lastItems = null)
    {
        $this->items = $items;
        $this->keyField = $keyField;
        $this->titleField = $titleField;
        $this->endItemIdx = null;

        if ($firstItems) {
            foreach ($firstItems as $k => $v) {
                $this->firstItems[] = array($k, $v);
                $this->excludedItems[] = $k;
            }
        }

        if ($lastItems) {
            foreach ($lastItems as $k => $v) {
                $this->lastItems[] = array($k, $v);
                $this->excludedItems[] = $k;
            }
        }
    }

    /**
     * Rewind the Iterator to the first element.
     *
     * @return mixed
     */
    public function rewind()
    {
        $this->firstItemIdx = 0;
        $this->endItemIdx = null;

        $rewoundItem = $this->items->rewind();

        if (isset($this->firstItems[$this->firstItemIdx])) {
            return $this->firstItems[$this->firstItemIdx][1];
        } else {
            if ($rewoundItem) {
                return $this->extractValue($rewoundItem, $this->titleField);
            } else {
                if (!$this->items->valid() && $this->lastItems) {
                    $this->endItemIdx = 0;

                    return $this->lastItems[0][1];
                }
            }
        }
    }

    /**
     * Return the current element.
     *
     * @return mixed
     */
    public function current()
    {
        if (($this->endItemIdx !== null) && isset($this->lastItems[$this->endItemIdx])) {
            return $this->lastItems[$this->endItemIdx][1];
        } else {
            if (isset($this->firstItems[$this->firstItemIdx])) {
                return $this->firstItems[$this->firstItemIdx][1];
            }
        }
        return $this->extractValue($this->items->current(), $this->titleField);
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
     * Return the key of the current element.
     *
     * @return string
     */
    public function key()
    {
        if (($this->endItemIdx !== null) && isset($this->lastItems[$this->endItemIdx])) {
            return $this->lastItems[$this->endItemIdx][0];
        } else {
            if (isset($this->firstItems[$this->firstItemIdx])) {
                return $this->firstItems[$this->firstItemIdx][0];
            } else {
                return $this->extractValue($this->items->current(), $this->keyField);
            }
        }
    }

    /**
     * Move forward to next element.
     *
     * @return mixed
     */
    public function next()
    {
        $this->firstItemIdx++;

        if (isset($this->firstItems[$this->firstItemIdx])) {
            return $this->firstItems[$this->firstItemIdx][1];
        } else {
            if (!isset($this->firstItems[$this->firstItemIdx - 1])) {
                $this->items->next();
            }

            if ($this->excludedItems) {
                while (($c = $this->items->current()) && in_array($c->{$this->keyField}, $this->excludedItems, true)) {
                    $this->items->next();
                }
            }
        }

        if (!$this->items->valid()) {
            // iterator has passed the preface items, off the end of the items
            // list. Track through the end items to go through to the next
            if ($this->endItemIdx === null) {
                $this->endItemIdx = -1;
            }

            $this->endItemIdx++;

            if (isset($this->lastItems[$this->endItemIdx])) {
                return $this->lastItems[$this->endItemIdx];
            }

            return false;
        }
    }

    /**
     * Checks if current position is valid.
     *
     * @return boolean
     */
    public function valid()
    {
        return (
            (isset($this->firstItems[$this->firstItemIdx])) ||
            (($this->endItemIdx !== null) && isset($this->lastItems[$this->endItemIdx])) ||
            $this->items->valid()
        );
    }
}
