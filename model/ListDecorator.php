<?php
/**
 * A base class for decorators that wrap around a list to provide additional
 * functionality. It passes through list methods to the underlying list
 * implementation.
 *
 * @package framework
 * @subpackage model
 */
abstract class SS_ListDecorator extends ViewableData implements SS_List, SS_Sortable, SS_Filterable, SS_Limitable {

	/**
	 * @var SS_List
	 */
	protected $list;

	public function __construct(SS_List $list) {
		$this->list     = $list;
		$this->failover = $this->list;

		parent::__construct();
	}

	/**
	 * Returns the list this decorator wraps around.
	 *
	 * @return SS_List
	 */
	public function getList() {
		return $this->list;
	}

	// PROXIED METHODS ---------------------------------------------------------

	public function offsetExists($key) {
		return $this->list->offsetExists($key);
	}

	public function offsetGet($key) {
		return $this->list->offsetGet($key);
	}

	public function offsetSet($key, $value) {
		$this->list->offsetSet($key, $value);
	}

	public function offsetUnset($key) {
		$this->list->offsetUnset($key);
	}

	public function toArray($index = null) {
		return $this->list->toArray($index);
	}

	public function toNestedArray($index = null){
		return $this->list->toNestedArray($index);
	}

	public function add($item) {
		$this->list->add($item);
	}

	public function remove($itemObject) {
		$this->list->remove($itemObject);
	}

	public function getIterator() {
		return $this->list->getIterator();
	}

	public function exists() {
		return $this->list->exists();
	}

	public function First() {
		return $this->list->First();
	}

	public function Last() {
		return $this->list->Last();
	}

	public function TotalItems() {
		return $this->list->TotalItems();
	}

	public function Count() {
		return $this->list->Count();
	}

	public function forTemplate() {
		return $this->list->forTemplate();
	}

	public function map($index = 'ID', $titleField = 'Title', $emptyString = null, $sort = false) {
		return $this->list->map($index, $titleField, $emptyString, $sort);
	}

	public function find($key, $value) {
		return $this->list->find($key, $value);
	}

	public function column($value = 'ID') {
		return $this->list->column($value);
	}
	
	public function each($callback) {
		return $this->list->each($callback);
	}

	public function canSortBy($by) {
		return $this->list->canSortBy($by);
	}

	public function reverse() {
		return $this->list->reverse();
	}

	/**
	 * Sorts this list by one or more fields. You can either pass in a single
	 * field name and direction, or a map of field names to sort directions.
	 *
	 * @example $list->sort('Name'); // default ASC sorting
	 * @example $list->sort('Name DESC'); // DESC sorting
	 * @example $list->sort('Name', 'ASC');
	 * @example $list->sort(array('Name'=>'ASC,'Age'=>'DESC'));
	 */
	public function sort() {
		$args = func_get_args();
		return call_user_func_array(array($this->list, 'sort'), $args);
	}

	public function canFilterBy($by) {
		return $this->list->canFilterBy($by);
	}

	/**
	 * Filter the list to include items with these charactaristics
	 *
	 * @example $list->filter('Name', 'bob'); // only bob in list
	 * @example $list->filter('Name', array('aziz', 'bob'); // aziz and bob in list
	 * @example $list->filter(array('Name'=>'bob, 'Age'=>21)); // bob or someone with Age 21
	 * @example $list->filter(array('Name'=>'bob, 'Age'=>array(21, 43))); // bob or anyone with Age 21 or 43
	 */
	public function filter(){
		$args = func_get_args();
		return call_user_func_array(array($this->list, 'filter'), $args);
	}

	/**
	 * Note that, in the current implementation, the filtered list will be an ArrayList, but this may change in a
	 * future implementation.
	 * @see SS_Filterable::filterByCallback()
	 *
	 * @example $list = $list->filterByCallback(function($item, $list) { return $item->Age == 9; })
	 * @param callable $callback
	 * @return ArrayList (this may change in future implementations)
	 */
	public function filterByCallback($callback) {
		if(!is_callable($callback)) {
			throw new LogicException(sprintf(
				"SS_Filterable::filterByCallback() passed callback must be callable, '%s' given",
				gettype($callback)
			));
		}
		$output = ArrayList::create();
		foreach($this->list as $item) {
			if(call_user_func($callback, $item, $this->list)) $output->push($item);
		}
		return $output;
	}

	public function limit($limit, $offset = 0) {
		return $this->list->limit($limit, $offset);
	}

	/**
	 * Exclude the list to not contain items with these charactaristics
	 *
	 * @example $list->exclude('Name', 'bob'); // exclude bob from list
	 * @example $list->exclude('Name', array('aziz', 'bob'); // exclude aziz and bob from list
	 * @example $list->exclude(array('Name'=>'bob, 'Age'=>21)); // exclude bob or someone with Age 21
	 * @example $list->exclude(array('Name'=>'bob, 'Age'=>array(21, 43))); // exclude bob or anyone with Age 21 or 43
	 */
	public function exclude(){
		$args = func_get_args();
		return call_user_func_array(array($this->list, 'exclude'), $args);
	}

	public function debug() {
		return $this->list->debug();
	}

}
