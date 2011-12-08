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
	public function map($keyfield = 'ID', $titlefield = 'Title');

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
	 * @param  string $colName
	 * @return array
	 */
	public function column($colName = "ID");

	/**
	 * Returns TRUE if the list can be sorted by a field.
	 *
	 * @param  string $by
	 * @return bool
	 */
	public function canSortBy($by);

	/**
	 * Sorts this list by one or more fields. You can either pass in a single
	 * field name and direction, or a map of field names to sort directions.
	 *
	 * @example $list->sort('Name'); // default ASC sorting
	 * @example $list->sort('Name DESC'); // DESC sorting
	 * @example $list->sort('Name', 'ASC');
	 * @example $list->sort(array('Name'=>'ASC,'Age'=>'DESC'));
	 */
	public function sort();
	
	/**
	 * Filter the list to include items with these charactaristics
	 * 
	 * @example $list->filter('Name', 'bob'); // only bob in the list
	 * @example $list->filter('Name', array('aziz', 'bob'); // aziz and bob in list
	 * @example $list->filter(array('Name'=>'bob, 'Age'=>21)); // bob with the age 21
	 * @example $list->filter(array('Name'=>'bob, 'Age'=>array(21, 43))); // bob with the Age 21 or 43
	 * @example $list->filter(array('Name'=>array('aziz','bob'), 'Age'=>array(21, 43))); // aziz with the age 21 or 43 and bob with the Age 21 or 43
	 */
	public function filter();
	
	/**
	 * Exclude the list to not contain items with these charactaristics
	 *
	 * @example $list->exclude('Name', 'bob'); // exclude bob from list
	 * @example $list->exclude('Name', array('aziz', 'bob'); // exclude aziz and bob from list
	 * @example $list->exclude(array('Name'=>'bob, 'Age'=>21)); // exclude bob that has Age 21
	 * @example $list->exclude(array('Name'=>'bob, 'Age'=>array(21, 43))); // exclude bob with Age 21 or 43
	 * @example $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43))); // bob age 21 or 43, phil age 21 or 43 would be excluded
	 */
	public function exclude();

}