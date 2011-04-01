<?php
/**
 * A base class for decorators that wrap around a list to provide additional
 * functionality. It extends {@link DataObjectSet} so that places which expect
 * a list are passed one.
 *
 * @package    sapphire
 * @subpackage model
 */
abstract class SS_ListDecorator extends DataObjectSet {

	protected $list;

	public function __construct(DataObjectSet $list) {
		$this->list = $list;
		parent::__construct();
	}

	/**
	 * Returns the list this decorator wraps around.
	 *
	 * @return DataObjectSet
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

	public function destroy() {
		$this->list->destroy();
	}

	public function emptyItems() {
		$this->list->emptyItems();
	}

	public function toArray($index = null) {
		return $this->list->toArray($index);
	}

	public function toNestedArray($index = null){
		return $this->list->toNestedArray($index);
	}

	public function push($item, $key = null) {
		$this->list->push($item, $key);
	}

	public function insertFirst($item, $key = null) {
		$this->list->insertFirst($item, $key);
	}

	public function unshift($item) {
		$this->list->unshift($item);
	}

	public function shift() {
		return $this->list->shift();
	}

	public function pop() {
		return $this->list->pop();
	}

	public function remove($itemObject) {
		$this->list->remove($itemObject);
	}

	public function replace($itemOld, $itemNew) {
		$this->list->replace($itemOld, $itemNew);
	}

	public function merge($anotherSet){
		$this->list->merge($anotherSet);
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

	public function UL() {
		return $this->list->UL();
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

	public function groupBy($index){
		return $this->list->groupBy($index);
	}

	public function GroupedBy($index, $childControl = "Children") {
		return $this->list->GroupedBy($index, $childControl);
	}

	public function buildNestedUL($nestingLevels, $ulExtraAttributes = '') {
		return $this->list->buildNestedUL($nestingLevels, $ulExtraAttributes);
	}

	public function getChildrenAsUL($nestingLevels, $level = 0, $template = "<li id=\"record-\$ID\" class=\"\$EvenOdd\">\$Title", $ulExtraAttributes = null, &$itemCount = 0) {
		return $this->list->getChildrenAsUL(
			$nestingLevels,
			$level,
			$template,
			$ulExtraAttributes,
			$itemCount);
	}

	public function sort($fieldname, $direction = "ASC") {
		$this->list->sort($fieldname, $direction);
	}

	public function removeDuplicates($field = 'ID') {
		$this->list->removeDuplicates($field);
	}

	public function debug() {
		return $this->list->debug();
	}

	public function groupWithParents($groupField, $groupClassName, $sortParents = null, $parentField = 'ID', $collapse = false, $requiredParents = null) {
		return $this->list->groupWithParents(
			$groupField,
			$groupClassName,
			$sortParents,
			$parentField,
			$collapse,
			$requiredParents);
	}

	public function addWithoutWrite($field) {
		$this->list->addWithoutWrite($field);
	}

	public function containsIDs($idList) {
		return $this->list->condaintsIDs($idList);
	}

	public function onlyContainsIDs($idList) {
		return $this->list->onlyContainsIDs($idList);
	}

}