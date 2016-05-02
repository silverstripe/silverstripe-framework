<?php

/**
 * Creates a map from an SS_List by defining a key column and a value column.
 *
 * @package framework
 * @subpackage model
 */
class SS_Map implements ArrayAccess, Countable, IteratorAggregate {

	protected $list, $keyField, $valueField;

	/**
	 * @see SS_Map::unshift()
	 *
	 * @var array $firstItems
	 */
	protected $firstItems = array();

	/**
	 * @see SS_Map::push()
	 *
	 * @var array $lastItems
	 */
	protected $lastItems = array();

	/**
	 * Construct a new map around an SS_list.
	 *
	 * @param $list The list to build a map from
	 * @param $keyField The field to use as the key of each map entry
	 * @param $valueField The field to use as the value of each map entry
	 */
	public function __construct(SS_List $list, $keyField = "ID", $valueField = "Title") {
		$this->list = $list;
		$this->keyField = $keyField;
		$this->valueField = $valueField;
	}

	/**
	 * Set the key field for this map.
	 *
	 * @var string $keyField
	 */
	public function setKeyField($keyField) {
		$this->keyField = $keyField;
	}

	/**
	 * Set the value field for this map.
	 *
	 * @var string $valueField
	 */
	public function setValueField($valueField) {
		$this->valueField = $valueField;
	}

	/**
	 * Return an array equivalent to this map.
	 *
	 * @return array
	 */
	public function toArray() {
		$array = array();

		foreach($this as $k => $v) {
			$array[$k] = $v;
		}

		return $array;
	}

	/**
	 * Return all the keys of this map.
	 *
	 * @return array
	 */
	public function keys() {
		return array_keys($this->toArray());
	}

	/**
	 * Return all the values of this map.
	 *
	 * @return array
	 */
	public function values() {
		return array_values($this->toArray());
	}

	/**
	 * Unshift an item onto the start of the map.
	 *
	 * Stores the value in addition to the {@link DataQuery} for the map.
	 *
	 * @var string $key
	 * @var mixed $value
	 */
	public function unshift($key, $value) {
		$oldItems = $this->firstItems;
		$this->firstItems = array(
			$key => $value
		);

		if($oldItems) {
			$this->firstItems = $this->firstItems + $oldItems;
		}

		return $this;
	}

	/**
	 * Pushes an item onto the end of the map.
	 *
	 * @var string $key
	 * @var mixed $value
	 */
	public function push($key, $value) {
		$oldItems = $this->lastItems;

		$this->lastItems = array(
			$key => $value
		);

		if($oldItems) {
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
	public function offsetExists($key) {
		if(isset($this->firstItems[$key])) {
			return true;
		}

		if(isset($this->lastItems[$key])) {
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
	public function offsetGet($key) {
		if(isset($this->firstItems[$key])) {
			return $this->firstItems[$key];
		}

		if(isset($this->lastItems[$key])) {
			return $this->lastItems[$key];
		}

		$record = $this->list->find($this->keyField, $key);

		if($record) {
			$col = $this->valueField;

			return $record->$col;
		}

		return null;
	}

	/**
	 * Sets a value in the map by a given key that has been set via
	 * {@link SS_Map::push()} or {@link SS_Map::unshift()}
	 *
	 * Keys in the map cannot be set since these values are derived from a
	 * {@link DataQuery} instance. In this case, use {@link SS_Map::toArray()}
	 * and manipulate the resulting array.
	 *
	 * @var string $key
	 * @var mixed $value
	 */
	public function offsetSet($key, $value) {
		if(isset($this->firstItems[$key])) {
			return $this->firstItems[$key] = $value;
		}

		if(isset($this->lastItems[$key])) {
			return $this->lastItems[$key] = $value;
		}

		user_error(
			"SS_Map is read-only. Please use $map->push($key, $value) to append values",
			E_USER_ERROR
		);
	}

	/**
	 * Removes a value in the map by a given key which has been added to the map
	 * via {@link SS_Map::push()} or {@link SS_Map::unshift()}
	 *
	 * Keys in the map cannot be unset since these values are derived from a
	 * {@link DataQuery} instance. In this case, use {@link SS_Map::toArray()}
	 * and manipulate the resulting array.
	 *
	 * @var string $key
	 * @var mixed $value
	 */
	public function offsetUnset($key) {
		if(isset($this->firstItems[$key])) {
			unset($this->firstItems[$key]);

			return;
		}

		if(isset($this->lastItems[$key])) {
			unset($this->lastItems[$key]);

			return;
		}

		user_error(
			"SS_Map is read-only. Unset cannot be called on keys derived from the DataQuery",
			E_USER_ERROR
		);
	}

	/**
	 * Returns an SS_Map_Iterator instance for iterating over the complete set
	 * of items in the map.
	 *
	 * Satisfies the IteratorAggreagte interface.
	 *
	 * @return SS_Map_Iterator
	 */
	public function getIterator() {
		return new SS_Map_Iterator(
			$this->list->getIterator(),
			$this->keyField,
			$this->valueField,
			$this->firstItems,
			$this->lastItems
		);
	}

	/**
	 * Returns the count of items in the list including the additional items set
	 * through {@link SS_Map::push()} and {@link SS_Map::unshift}.
	 *
	 * @return int
	 */
	public function count() {
		return $this->list->count() +
			count($this->firstItems) +
			count($this->lastItems);
	}
}

/**
 * Builds a map iterator around an Iterator.  Called by SS_Map
 *
 * @package framework
 * @subpackage model
 */
class SS_Map_Iterator implements Iterator {

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
	 * @param array $fristItems An optional map of items to show first
	 * @param array $lastItems An optional map of items to show last
	 */
	public function __construct(Iterator $items, $keyField, $titleField, $firstItems = null, $lastItems = null) {
		$this->items = $items;
		$this->keyField = $keyField;
		$this->titleField = $titleField;
		$this->endItemIdx = null;

		if($firstItems) {
			foreach($firstItems as $k => $v) {
				$this->firstItems[] = array($k,$v);
				$this->excludedItems[] = $k;
			}
		}

		if($lastItems) {
			foreach($lastItems as $k => $v) {
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
	public function rewind() {
		$this->firstItemIdx = 0;
		$this->endItemIdx = null;

		$rewoundItem = $this->items->rewind();

		if(isset($this->firstItems[$this->firstItemIdx])) {
			return $this->firstItems[$this->firstItemIdx][1];
		} else {
			if($rewoundItem) {
				if($rewoundItem->hasMethod($this->titleField)) {
					return $rewoundItem->{$this->titleField}();
				}

				return $rewoundItem->{$this->titleField};
			} else if(!$this->items->valid() && $this->lastItems) {
				$this->endItemIdx = 0;

				return $this->lastItems[0][1];
			}
		}
	}

	/**
	 * Return the current element.
	 *
	 * @return mixed
	 */
	public function current() {
		if(($this->endItemIdx !== null) && isset($this->lastItems[$this->endItemIdx])) {
			return $this->lastItems[$this->endItemIdx][1];
		} else if(isset($this->firstItems[$this->firstItemIdx])) {
			return $this->firstItems[$this->firstItemIdx][1];
		} else {
			if($this->items->current()->hasMethod($this->titleField)) {
				return $this->items->current()->{$this->titleField}();
			}

			return $this->items->current()->{$this->titleField};
		}
	}

	/**
	 * Return the key of the current element.
	 *
	 * @return string
	 */
	public function key() {
		if(($this->endItemIdx !== null) && isset($this->lastItems[$this->endItemIdx])) {
			return $this->lastItems[$this->endItemIdx][0];
		} else if(isset($this->firstItems[$this->firstItemIdx])) {
			return $this->firstItems[$this->firstItemIdx][0];
		} else {
			return $this->items->current()->{$this->keyField};
		}
	}

	/**
	 * Move forward to next element.
	 *
	 * @return mixed
	 */
	public function next() {
		$this->firstItemIdx++;

		if(isset($this->firstItems[$this->firstItemIdx])) {
			return $this->firstItems[$this->firstItemIdx][1];
		} else {
			if(!isset($this->firstItems[$this->firstItemIdx-1])) {
				$this->items->next();
			}

			if($this->excludedItems) {
				while(($c = $this->items->current()) && in_array($c->{$this->keyField}, $this->excludedItems, true)) {
					$this->items->next();
				}
			}
		}

		if(!$this->items->valid()) {
			// iterator has passed the preface items, off the end of the items
			// list. Track through the end items to go through to the next
			if($this->endItemIdx === null) {
				$this->endItemIdx = -1;
			}

			$this->endItemIdx++;

			if(isset($this->lastItems[$this->endItemIdx])) {
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
	public function valid() {
		return (
			(isset($this->firstItems[$this->firstItemIdx])) ||
			(($this->endItemIdx !== null) && isset($this->lastItems[$this->endItemIdx])) ||
			$this->items->valid()
		);
	}
}
