<?php
/**
 * A list object that wraps around an array of objects or arrays.
 *
 * @package    sapphire
 * @subpackage model
 */
class ArrayList extends ViewableData implements SS_List {

	/**
	 * @var array
	 */
	protected $items;

	public function __construct(array $items = array()) {
		$this->items = $items;
		parent::__construct();
	}

	public function count() {
		return count($this->items);
	}

	public function exists() {
		return (bool) count($this);
	}

	public function getIterator() {
		return new ArrayIterator($this->items);
	}

	public function toArray() {
		return $this->items;
	}

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

	public function getRange($offset, $length) {
		return new ArrayList(array_slice($this->items, $offset, $length));
	}

	public function add($item) {
		$this->push($item);
	}

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
			$value = $this->extract($item, $field);

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
	 * Unshifts an item onto the beginning of the list.
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

	public function first() {
		return reset($this->items);
	}

	public function last() {
		return end($this->items);
	}

	public function map($keyfield, $titlefield) {
		$map = array();
		foreach ($this->items as $item) {
			$map[$this->extract($item, $keyfield)] = $this->extract($item, $titlefield);
		}
		return $map;
	}

	public function find($key, $value) {
		foreach ($this->items as $item) {
			if ($this->extract($item, $key) == $value) return $item;
		}
	}

	public function column($field = 'ID') {
		$result = array();
		foreach ($this->items as $item) {
			$result[] = $this->extract($item, $field);
		}
		return $result;
	}

	public function canSortBy($by) {
		return true;
	}

	/**
	 * Sorts this list by one or more fields. You can either pass in a single
	 * field name and direction, or a map of field names to sort directions.
	 *
	 * @param string|array $by
	 * @param string $dir
	 * @see   SS_List::sort()
	 */
	public function sort($by, $dir = 'ASC') {
		$sorts = array();

		if (!is_array($by)) {
			$by = array($by => $dir);
		}

		foreach ($by as $field => $dir) {
			$dir  = strtoupper($dir) == 'DESC' ? SORT_DESC : SORT_ASC;
			$vals = array();

			foreach ($this->items as $item) {
				$vals[] = $this->extract($item, $field);
			}

			$sorts[] = $vals;
			$sorts[] = $dir;
		}

		$sorts[] = &$this->items;
		call_user_func_array('array_multisort', $sorts);
	}

	public function offsetExists($offset) {
		return array_key_exists($offset, $this->items);
	}

	public function offsetGet($offset) {
		if ($this->offsetExists($offset)) return $this->items[$offset];
	}

	public function offsetSet($offset, $value) {
		$this->items[$offset] = $value;
	}

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
	protected function extract($item, $key) {
		if (is_object($item)) {
			return $item->$key;
		} else {
			if (array_key_exists($key, $item)) return $item[$key];
		}
	}

}