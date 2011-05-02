<?php
/**
 * A base class for decorators that wrap around a list to provide additional
 * functionality. It passes through list methods to the underlying list 
 * implementation.
 *
 * @package    sapphire
 * @subpackage model
 */
abstract class SS_ListDecorator extends ViewableData implements SS_List {

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

	public function getRange($offset, $length) {
		return $this->list->getRange($offset, $length);
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

	public function canSortBy($by) {
		return $this->list->canSortBy($by);
	}

	public function sort($fieldname, $direction = "ASC") {
		$this->list->sort($fieldname, $direction);
	}

	public function debug() {
		return $this->list->debug();
	}

}