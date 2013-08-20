<?php
/**
 * A list object that wraps around an array of objects or arrays.
 *
 * Note that (like DataLists), the implementations of the methods from SS_Filterable, SS_Sortable and
 * SS_Limitable return a new instance of ArrayList, rather than modifying the existing instance.
 *
 * For easy reference, methods that operate in this way are:
 *
 *   - limit
 *   - reverse
 *   - sort
 *   - filter
 *   - exclude
 *
 * @package framework
 * @subpackage model
 */
class ArrayList extends ViewableData implements SS_List, SS_Filterable, SS_Sortable, SS_Limitable {

	/**
	 * Holds the items in the list
	 * 
	 * @var array
	 */
	protected $items = array();
	
	/**
	 *
	 * @param array $items - an initial array to fill this object with
	 */
	public function __construct(array $items = array()) {
		$this->items = array_values($items);
		parent::__construct();
	}
	
	/**
	 * Return the class of items in this list, by looking at the first item inside it.
	 */
	public function dataClass() {
		if(count($this->items) > 0) return get_class($this->items[0]);
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
		foreach($this->items as $i => $item) {
			if(is_array($item)) $this->items[$i] = new ArrayData($item);
		}
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
	 * Walks the list using the specified callback
	 *
	 * @param callable $callback
	 * @return DataList
	 */
	public function each($callback) {
		foreach($this as $item) {
			$callback($item);
		}
	}

	public function debug() {
		$val = "<h2>" . $this->class . "</h2><ul>";
		foreach($this->toNestedArray() as $item) {
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
	public function limit($length, $offset = 0) {
		if(!$length) {
			$length = count($this->items);
		}

		$list = clone $this;
		$list->items = array_slice($this->items, $offset, $length);

		return $list;
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
		$renumberKeys = false;
		foreach ($this->items as $key => $value) {
			if ($item === $value) {
				$renumberKeys = true;
				unset($this->items[$key]);
			}
		}
		if($renumberKeys) $this->items = array_values($this->items);
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
		$renumberKeys = false;

		foreach ($this->items as $key => $item) {
			$value = $this->extractValue($item, $field);

			if (array_key_exists($value, $seen)) {
				$renumberKeys = true;
				unset($this->items[$key]);
			}

			$seen[$value] = true;
		}

		if($renumberKeys) $this->items = array_values($this->items);
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
	 * Reverses an {@link ArrayList}
	 *
	 * @return ArrayList
	 */
	public function reverse() {
		$list = clone $this;
		$list->items = array_reverse($this->items);
		
		return $list;
	}
	
	/**
	 * Sorts this list by one or more fields. You can either pass in a single
	 * field name and direction, or a map of field names to sort directions.
	 *
	 * @return DataList
	 * @see SS_List::sort()
	 * @example $list->sort('Name'); // default ASC sorting
	 * @example $list->sort('Name DESC'); // DESC sorting
	 * @example $list->sort('Name', 'ASC');
	 * @example $list->sort(array('Name'=>'ASC,'Age'=>'DESC'));
	 */
	public function sort() {
		$args = func_get_args();
		
		if(count($args)==0){
			return $this;
		}
		if(count($args)>2){
			throw new InvalidArgumentException('This method takes zero, one or two arguments');
		}
		
		// One argument and it's a string
		if(count($args)==1 && is_string($args[0])){
			$column = $args[0];
			if(strpos($column, ' ') !== false) {
				throw new InvalidArgumentException("You can't pass SQL fragments to sort()");
			}
			$columnsToSort[$column] = SORT_ASC;

		} else if(count($args)==2){
			$columnsToSort[$args[0]]=(strtolower($args[1])=='desc')?SORT_DESC:SORT_ASC;

		} else if(is_array($args[0])) {
			foreach($args[0] as $column => $sort_order){
				$columnsToSort[$column] = (strtolower($sort_order)=='desc')?SORT_DESC:SORT_ASC;
			}
		} else {
			throw new InvalidArgumentException("Bad arguments passed to sort()");
		}

		// This the main sorting algorithm that supports infinite sorting params
		$multisortArgs = array();
		$values = array();
		foreach($columnsToSort as $column => $direction ) {
			// The reason these are added to columns is of the references, otherwise when the foreach
			// is done, all $values and $direction look the same
			$values[$column] = array();
			$sortDirection[$column] = $direction;
			// We need to subtract every value into a temporary array for sorting
			foreach($this->items as $index => $item) {
				$values[$column][] = $this->extractValue($item, $column);
			}
			// PHP 5.3 requires below arguments to be reference when using array_multisort together 
			// with call_user_func_array
			// First argument is the 'value' array to be sorted
			$multisortArgs[] = &$values[$column];
			// First argument is the direction to be sorted, 
			$multisortArgs[] = &$sortDirection[$column];
		}

		$list = clone $this;
		// As the last argument we pass in a reference to the items that all the sorting will be applied upon
		$multisortArgs[] = &$list->items;
		call_user_func_array('array_multisort', $multisortArgs);
		return $list;
	}
	
	/**
	 * Returns true if the given column can be used to filter the records.
	 * 
	 * It works by checking the fields available in the first record of the list.
	 */
	public function canFilterBy($by) {
		$firstRecord = $this->first();
		if ($firstRecord === false) {
			return false;
		}
		return array_key_exists($by, $firstRecord);
	}

	/**
	 * Filter the list to include items with these charactaristics
	 * 
	 * @return ArrayList
	 * @see SS_List::filter()
	 * @example $list->filter('Name', 'bob'); // only bob in the list
	 * @example $list->filter('Name', array('aziz', 'bob'); // aziz and bob in list
	 * @example $list->filter(array('Name'=>'bob, 'Age'=>21)); // bob with the Age 21 in list
	 * @example $list->filter(array('Name'=>'bob, 'Age'=>array(21, 43))); // bob with the Age 21 or 43
	 * @example $list->filter(array('Name'=>array('aziz','bob'), 'Age'=>array(21, 43))); 
	 *          // aziz with the age 21 or 43 and bob with the Age 21 or 43
	 */
	public function filter() {
		if(count(func_get_args())>2){
			throw new InvalidArgumentException('filter takes one array or two arguments');
		}
		
		if(count(func_get_args()) == 1 && !is_array(func_get_arg(0))){
			throw new InvalidArgumentException('filter takes one array or two arguments');
		}
		
		$keepUs = array();
		if(count(func_get_args())==2){
			$keepUs[func_get_arg(0)] = func_get_arg(1);
		}
		
		if(count(func_get_args())==1 && is_array(func_get_arg(0))){
			foreach(func_get_arg(0) as $column => $value) {
				$keepUs[$column] = $value;
			}
		}
		
		$itemsToKeep = array();
		foreach($this->items as $item){
			$keepItem = true;
			foreach($keepUs as $column => $value ) {
				if(is_array($value) && !in_array($this->extractValue($item, $column), $value)) {
					$keepItem = false;
				} elseif(!is_array($value) && $this->extractValue($item, $column) != $value) {
					$keepItem = false;
				}
			}
			if($keepItem) {
				$itemsToKeep[] = $item;
			}
		}

		$list = clone $this;
		$list->items = $itemsToKeep;
		return $list;
	}
	
	public function byID($id) {
		$firstElement = $this->filter("ID", $id)->first();
		if ($firstElement === false) {
			return null;
		}
		return $firstElement;
	}

	/**
	 * Exclude the list to not contain items with these charactaristics
	 *
	 * @return ArrayList
	 * @see SS_List::exclude()
	 * @example $list->exclude('Name', 'bob'); // exclude bob from list
	 * @example $list->exclude('Name', array('aziz', 'bob'); // exclude aziz and bob from list
	 * @example $list->exclude(array('Name'=>'bob, 'Age'=>21)); // exclude bob that has Age 21
	 * @example $list->exclude(array('Name'=>'bob, 'Age'=>array(21, 43))); // exclude bob with Age 21 or 43
	 * @example $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43)));
	 *          // bob age 21 or 43, phil age 21 or 43 would be excluded
	 */
	public function exclude() {
		if(count(func_get_args())>2){
			throw new InvalidArgumentException('exclude() takes one array or two arguments');
		}
		
		if(count(func_get_args()) == 1 && !is_array(func_get_arg(0))){
			throw new InvalidArgumentException('exclude() takes one array or two arguments');
		}
		
		$removeUs = array();
		if(count(func_get_args())==2){
			$removeUs[func_get_arg(0)] = func_get_arg(1);
		}
		
		if(count(func_get_args())==1 && is_array(func_get_arg(0))){
			foreach(func_get_arg(0) as $column => $excludeValue) {
				$removeUs[$column] = $excludeValue;
			}
		}


		$hitsRequiredToRemove = count($removeUs);
		$matches = array();
		foreach($removeUs as $column => $excludeValue) {
			foreach($this->items as $key => $item){
				if(!is_array($excludeValue) && $this->extractValue($item, $column) == $excludeValue) {
					$matches[$key]=isset($matches[$key])?$matches[$key]+1:1;
				} elseif(is_array($excludeValue) && in_array($this->extractValue($item, $column), $excludeValue)) {
					$matches[$key]=isset($matches[$key])?$matches[$key]+1:1;
				}
			}
		}

		$keysToRemove = array_keys($matches,$hitsRequiredToRemove);

		$itemsToKeep = array();
		foreach($this->items as $key => $value) {
			if(!in_array($key, $keysToRemove)) {
				$itemsToKeep[] = $value;
			}
		}

		$list = clone $this;
		$list->items = $itemsToKeep;
		return $list;
	}

	protected function shouldExclude($item, $args) {
		
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
