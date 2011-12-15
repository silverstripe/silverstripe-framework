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
	 * @return DataList
	 * @see SS_List::sort()
	 * @example $list->sort('Name'); // default ASC sorting
	 * @example $list->sort('Name DESC'); // DESC sorting
	 * @example $list->sort('Name', 'ASC');
	 * @example $list->sort(array('Name'=>'ASC,'Age'=>'DESC'));
	 */
	public function sort() {
		if(count(func_get_args())==0){
			return $this;
		}
		if(count(func_get_args())>2){
			user_error('This method takes zero, one or two arguments');
		}
		
		// One argument and it's a string
		if(count(func_get_args())==1 && is_string(func_get_arg(0))){
			// support 'old' style sorting syntax like "Name DESC, Group ASC"
			$pattern = '/([a-zA-Z1-9]+)\s?(DESC|ASC)?/';
			$args = func_get_arg(0);
			if($hits = preg_match_all($pattern, $args, $matches)) {
				for($idx=0;$idx<$hits;$idx++){
					$columnsToSort[$matches[1][$idx]] = (stristr($matches[2][$idx], 'desc'))?SORT_DESC:SORT_ASC;
				}
			}
		}

		if(count(func_get_args())==2){
			$columnsToSort[func_get_arg(0)]=(strtolower(func_get_arg(1))=='desc')?SORT_DESC:SORT_ASC;
		}

		if(is_array(func_get_arg(0))){
			foreach(func_get_arg(0) as $column => $sort_order){
				$columnsToSort[$column] = (strtolower($sort_order)=='desc')?SORT_DESC:SORT_ASC;
			}
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
		// As the last argument we pass in a reference to the items that all the sorting will be 
		// applied upon
		$multisortArgs[] = &$this->items;
		call_user_func_array('array_multisort', $multisortArgs);
		return $this;
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
	 * @example $list->filter(array('Name'=>array('aziz','bob'), 'Age'=>array(21, 43))); // aziz with the age 21 or 43 and bob with the Age 21 or 43
	 */
	public function filter() {
		if(count(func_get_args())>2){
			user_error('filter takes one array or two arguments');
		}
		
		if(count(func_get_args()) == 1 && !is_array(func_get_arg(0))){
			user_error('filter takes one array or two arguments');
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

		$this->items = $itemsToKeep;
		return $this;
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
	 * @example $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43))); // bob age 21 or 43, phil age 21 or 43 would be excluded
	 */
	public function exclude() {
		if(count(func_get_args())>2){
			user_error('exclude() takes one array or two arguments');
		}
		
		if(count(func_get_args()) == 1 && !is_array(func_get_arg(0))){
			user_error('exclude() takes one array or two arguments');
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

		$itemsToKeep = array();

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
		foreach($keysToRemove as $itemToRemoveIdx){
			$this->remove($this->items[$itemToRemoveIdx]);
		}
		return;
		
		return $this;
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