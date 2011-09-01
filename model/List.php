<?php
/**
 * An interface that a class can implement to be treated as a list container.
 *
 * @package    sapphire
 * @subpackage model
 */
interface SS_List extends ArrayAccess, Countable, IteratorAggregate {

	/**
	 * Returns all the items in the list in an array.
	 *
	 * @return arary
	 */
	public function toArray();

	/**
	 * Returns the contents of the list as an array of maps.
	 *
	 * @return array
	 */
	public function toNestedArray();

	/**
	 * Returns a subset of the items within the list.
	 *
	 * @param  int $offset
	 * @param  int $length
	 * @return SS_List
	 */
	public function getRange($offset, $length);

	/**
	 * Adds an item to the list, making no guarantees about where it will
	 * appear.
	 *
	 * @param mixed $item
	 */
	public function add($item);

	/**
	 * Removes an item from the list.
	 *
	 * @param mixed $item
	 */
	public function remove($item);

	/**
	 * Returns the first item in the list.
	 *
	 * @return mixed
	 */
	public function first();

	/**
	 * Returns the last item in the list.
	 *
	 * @return mixed
	 */
	public function last();

	/**
	 * Returns a map of a key field to a value field of all the items in the
	 * list.
	 *
	 * @param  string $keyfield
	 * @param  string $titlefield
	 * @return array
	 */
	function map($keyfield, $titlefield);

	/**
	 * Returns the first item in the list where the key field is equal to the
	 * value.
	 *
	 * @param  string $key
	 * @param  mixed $value
	 * @return mixed
	 */
	public function find($key, $value);

	/**
	 * Returns an array of a single field value for all items in the list.
	 *
	 * @param  string $field
	 * @return array
	 */
	public function column($field);

	/**
	 * Returns TRUE if the list can be sorted by a field.
	 *
	 * @param  string $by
	 * @return bool
	 */
	public function canSortBy($by);

	/**
	 * Sorts the list in place by a field on the items and direction.
	 *
	 * @param  string $by  The field name to sort by.
	 * @param  string $dir Either "ASC" or "DIR".
	 */
	public function sort($by, $dir = 'ASC');

}