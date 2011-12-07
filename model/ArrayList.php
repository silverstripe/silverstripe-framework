<?php
/**
 * A list object that wraps around an array of objects or arrays.
 *
 * @package    sapphire
 * @subpackage model
 */
class ArrayList extends ViewableData implements SS_List {

	/**
	 * Holds the items in the list
	 * 
	 * @var array
	 */
	protected $items;
	
	
	/**
	 * Synonym of the constructor. Can be chained with literate methods.
	 * ArrayList::create("SiteTree")->sort("Title") is legal, but
	 * new ArrayList("SiteTree")->sort("Title") is not.
	 * 
	 * @param array $items - an initial array to fill this object with
	 */
	public static function create(array $items = array()) {
		return new ArrayList($items);
	}
	
	/**
	 *
	 * @param array $items - an initial array to fill this object with
	 */
	public function __construct(array $items = array()) {
		$this->items = $items;
		parent::__construct();
	}

	/**
	 * Return the number of items in this list
	 *
	 * @return int
	 */
	public function count() {
		return count($this->items);
	}

	/**
	 * Returns true if this list has items
	 * 
	 * @return bool
	 */
	public function exists() {
		return (bool) count($this);
	}

	/**
	 * Returns an Iterator for this ArrayList.
	 * This function allows you to use ArrayList in foreach loops
	 *
	 * @return ArrayIterator 
	 */
	public function getIterator() {
		return new ArrayIterator($this->items);
	}

	/**
	 * Return an array of the actual items that this ArrayList contains.
	 *
	 * @return array 
	 */
	public function toArray() {
		return $this->items;
	}

	/**
	 * Return this list as an array and every object it as an sub array as well
	 * 
	 * @return array 
	 */
	public function toNestedArray() {
		$result = array();

		foreach ($this->items as $item) {
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

		return $result;
	}

	/**
	 * Get a sub-range of this dataobjectset as an array
	 * 
	 * @param int $offset
	 * @param int $length
	 * @return ArrayList 
	 */
	public function getRange($offset, $length) {
		return new ArrayList(array_slice($this->items, $offset, $length));
	}

	/**
	 * Add this $item into this list
	 *
	 * @param mixed $item 
	 */
	public function add($item) {
		$this->push($item);
	}

	/**
	 * Remove this item from this list
	 * 
	 * @param mixed $item 
	 */
	public function remove($item) {
		foreach ($this->items as $key => $value) {
			if ($item === $value) unset($this->items[$key]);
		}
	}

	/**
	 * Replaces an item in this list with another item.
	 *
	 * @param array|object $item
	 * @param array|object $with
	 * @return void;
	 */
	public function replace($item, $with) {
		foreach ($this->items as $key => $candidate) {
			if ($candidate === $item) {
				$this->items[$key] = $with;
				return;
			}
		}
	}

	/**
	 * Merges with another array or list by pushing all the items in it onto the
	 * end of this list.
	 *
	 * @param array|object $with
	 */
	public function merge($with) {
		foreach ($with as $item) $this->push($item);
	}

	/**
	 * Removes items from this list which have a duplicate value for a certain
	 * field. This is especially useful when combining lists.
	 *
	 * @param string $field
	 */
	public function removeDuplicates($field = 'ID') {
		$seen = array();

		foreach ($this->items as $key => $item) {
			$value = $this->extractValue($item, $field);

			if (array_key_exists($value, $seen)) {
				unset($this->items[$key]);
			}

			$seen[$value] = true;
		}
	}

	/**
	 * Pushes an item onto the end of this list.
	 *
	 * @param array|object $item
	 */
	public function push($item) {
		$this->items[] = $item;
	}

	/**
	 * Pops the last element off the end of the list and returns it.
	 *
	 * @return array|object
	 */
	public function pop() {
		return array_pop($this->items);
	}

	/**
	 * Add an item onto the beginning of the list.
	 *
	 * @param array|object $item
	 */
	public function unshift($item) {
		array_unshift($this->items, $item);
	}

	/**
	 * Shifts the item off the beginning of the list and returns it.
	 *
	 * @return array|object
	 */
	public function shift() {
		return array_shift($this->items);
	}

	/**
	 * Returns the first item in the list
	 *
	 * @return mixed
	 */
	public function first() {
		return reset($this->items);
	}

	/**
	 * Returns the last item in the list
	 *
	 * @return mixed
	 */
	public function last() {
		return end($this->items);
	}

	/**
	 * Returns a map of this list
	 *
	 * @param type $keyfield - the 'key' field of the result array
	 * @param type $titlefield - the value field of the result array
	 * @return array 
	 */
	public function map($keyfield = 'ID', $titlefield = 'Title') {
		$map = array();
		foreach ($this->items as $item) {
			$map[$this->extractValue($item, $keyfield)] = $this->extractValue($item, $titlefield);
		}
		return $map;
	}

	/**
	 * Find the first item of this list where the given key = value
	 *
	 * @param type $key
	 * @param type $value
	 * @return type 
	 */
	public function find($key, $value) {
		foreach ($this->items as $item) {
			if ($this->extractValue($item, $key) == $value) return $item;
		}
	}

	/**
	 * Returns an array of a single field value for all items in the list.
	 *
	 * @param string $colName
	 * @return array
	 */
	public function column($colName = 'ID') {
		$result = array();
		foreach ($this->items as $item) {
			$result[] = $this->extractValue($item, $colName);
		}
		return $result;
	}

	/**
	 * You can always sort a ArrayList
	 *
	 * @param string $by
	 * @return bool
	 */
	public function canSortBy($by) {
		return true;
	}

	/**
	 * Sorts this list by one or more fields. You can either pass in a single
	 * field name and direction, or a map of field names to sort directions.
	 *
	 * @param string|array $by
	 * @param string $sortDirection
	 * @see SS_List::sort()
	 * @link http://php.net/manual/en/function.array-multisort.php
	 * @example $list->sort('Name', 'ASC');
	 * @example $list->sort(array('Name'=>'ASC,'Age'=>'DESC');
	 */
	public function sort($by, $sortDirection = 'ASC') {
		$sorts = array();

		if(!is_array($by)) {
			$by = array($by => $sortDirection);
		}

		foreach ($by as $field => $sortDirection) {
			$sortDirection  = strtoupper($sortDirection) == 'DESC' ? SORT_DESC : SORT_ASC;
			$values = array();
			foreach($this->items as $item) {
				$values[] = $this->extractValue($item, $field);
			}
			$sorts[] = &$values;
			$sorts[] = &$sortDirection;
		}
		$sorts[] = &$this->items;
		call_user_func_array('array_multisort', $sorts);
	}

	/**
	 * Returns whether an item with $key exists
	 * 
	 * @param mixed $key
	 * @return bool
	 */
	public function offsetExists($offset) {
		return array_key_exists($offset, $this->items);
	}
	
	/**
	 * Returns item stored in list with index $key
	 * 
	 * @param mixed $key
	 * @return DataObject
	 */
	public function offsetGet($offset) {
		if ($this->offsetExists($offset)) return $this->items[$offset];
	}

	/**
	 * Set an item with the key in $key
	 * 
	 * @param mixed $key
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value) {
		$this->items[$offset] = $value;
	}

	/**
	 * Unset an item with the key in $key
	 * 
	 * @param mixed $key
	 */
	public function offsetUnset($offset) {
		unset($this->items[$offset]);
	}

	/**
	 * Extracts a value from an item in the list, where the item is either an
	 * object or array.
	 *
	 * @param  array|object $item
	 * @param  string $key
	 * @return mixed
	 */
	protected function extractValue($item, $key) {
		if (is_object($item)) {
			return $item->$key;
		} else {
			if (array_key_exists($key, $item)) return $item[$key];
		}
	}

}