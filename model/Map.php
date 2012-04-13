<?php

/**
 * Creates a map from an SS_List by defining a key column and a value column.
 * 
 * @package framework
 * @subpackage model
 */
class SS_Map implements ArrayAccess, Countable, IteratorAggregate {
	protected $list, $keyField, $valueField;
	
	protected $firstItems = array();

	/**
	 * Construct a new map around an SS_list.
	 * @param $list The list to build a map from
	 * @param $keyField The field to use as the key of each map entry
	 * @param $valueField The field to use as the value of each map entry
	 */
	function __construct(SS_List $list, $keyField = "ID", $valueField = "Title") {
		$this->list = $list;
		$this->keyField = $keyField;
		$this->valueField = $valueField;
	}
	
	/**
	 * Set the key field for this map
	 */
	function setKeyField($keyField) {
		$this->keyField = $keyField;
	}

	/**
	 * Set the value field for this map
	 */
	function setValueField($valueField) {
		$this->valueField = $valueField;
	}
	
	/**
	 * Return an array equivalent to this map
	 */
	function toArray() {
		$array = array();
		foreach($this as $k => $v) {
			$array[$k] = $v;
		}
		return $array;
	}

	/**
	 * Return all the keys of this map
	 */
	function keys() {
		$output = array();
		foreach($this as $k => $v) {
			$output[] = $k;
		}
		return $output;
	}

	/**
	 * Return all the values of this map
	 */
	function values() {
		$output = array();
		foreach($this as $k => $v) {
			$output[] = $v;
		}
		return $output;
	}

	/**
	 * Unshift an item onto the start of the map
	 */
	function unshift($key, $value) {
		$oldItems = $this->firstItems;
		$this->firstItems = array($key => $value);
		if($oldItems) $this->firstItems = $this->firstItems + $oldItems;
	}

	// ArrayAccess
	
	function offsetExists($key) {
		if(isset($this->firstItems[$key])) return true;
		
		$record = $this->list->find($this->keyField, $key);
		return $record != null;
	}
	function offsetGet($key) {
		if(isset($this->firstItems[$key])) return $this->firstItems[$key];

		$record = $this->list->find($this->keyField, $key);
		if($record) {
			$col = $this->valueField;
			return $record->$col;
		} else {
			return null;
		}
	}
	function offsetSet($key, $value) {
		if(isset($this->firstItems[$key])) return $this->firstItems[$key] = $value;

		user_error("SS_Map is read-only", E_USER_ERROR);
	}
	function offsetUnset($key) {
		if(isset($this->firstItems[$key])) {
			unset($this->firstItems[$key]);
			return;
		}
		
		user_error("SS_Map is read-only", E_USER_ERROR);
	}
	
	// IteratorAggreagte
	
	function getIterator() {
		return new SS_Map_Iterator($this->list->getIterator(), $this->keyField, $this->valueField, $this->firstItems);
	}

	// Countable
	
	function count() {
		return $this->list->count();
	}
}

/**
 * Builds a map iterator around an Iterator.  Called by SS_Map
 * @package framework
 * @subpackage model
 */
class SS_Map_Iterator implements Iterator {
	protected $items;
	protected $keyField, $titleField;
	
	protected $firstItemIdx = 0;
	protected $firstItems = array();
	protected $excludedItems = array();
	
	/**
	 * @param $items The iterator to build this map from
	 * @param $keyField The field to use for the keys
	 * @param $titleField The field to use for the values
	 * @param $fistItems An optional map of items to show first
	 */
	function __construct(Iterator $items, $keyField, $titleField, $firstItems = null) {
		$this->items = $items;
		$this->keyField = $keyField;
		$this->titleField = $titleField;
		
		foreach($firstItems as $k => $v) {
			$this->firstItems[] = array($k,$v);
			$this->excludedItems[] = $k;
		}
		
	}

	// Iterator functions
	
	public function rewind() {
		$this->firstItemIdx = 0;
		$rewoundItem = $this->items->rewind();

		if(isset($this->firstItems[$this->firstItemIdx])) {
			return $this->firstItems[$this->firstItemIdx][1];
		} else {
			if($rewoundItem) return ($rewoundItem->hasMethod($this->titleField))
				? $rewoundItem->{$this->titleField}()
				: $rewoundItem->{$this->titleField};
		}
		
	}
	
	public function current() {
		if(isset($this->firstItems[$this->firstItemIdx])) {
			return $this->firstItems[$this->firstItemIdx][1];
		} else {
			return ($this->items->current()->hasMethod($this->titleField))
				? $this->items->current()->{$this->titleField}()
				: $this->items->current()->{$this->titleField};
		}
	}
	
	public function key() {
		if(isset($this->firstItems[$this->firstItemIdx])) {
			return $this->firstItems[$this->firstItemIdx][0];
			
		} else {
			return $this->items->current()->{$this->keyField};
		}
	}
	
	public function next() {
		$this->firstItemIdx++;
		if(isset($this->firstItems[$this->firstItemIdx])) {
			return $this->firstItems[$this->firstItemIdx][1];
			
		} else {
			if(!isset($this->firstItems[$this->firstItemIdx-1])) $this->items->next();

			if($this->excludedItems) while(($c = $this->items->current()) && in_array($c->{$this->keyField}, $this->excludedItems, true)) {
				$this->items->next();
			}
		}
	}
	
	public function valid() {
		return $this->items->valid();
	}
}
