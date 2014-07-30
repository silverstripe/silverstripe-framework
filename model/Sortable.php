<?php

/**
 * Additional interface for {@link SS_List} classes that are sortable.
 *
 * All methods in this interface are immutable - they should return new instances with the sort
 * applied, rather than applying the sort in place
 *
 * @see SS_List, SS_Filterable, SS_Limitable
 * @package framework
 * @subpackage model
 */
interface SS_Sortable extends SS_List {

	/**
	 * Returns TRUE if the list can be sorted by a field.
	 *
	 * @param  string $by
	 * @return bool
	 */
	public function canSortBy($by);

	/**
	 * Return a new instance of this list that is sorted by one or more fields. You can either pass in a single
	 * field name and direction, or a map of field names to sort directions.
	 *
	 * @return SS_Sortable
	 * @example $list = $list->sort('Name'); // default ASC sorting
	 * @example $list = $list->sort('Name DESC'); // DESC sorting
	 * @example $list = $list->sort('Name', 'ASC');
	 * @example $list = $list->sort(array('Name'=>'ASC,'Age'=>'DESC'));
	 */
	public function sort();


	/**
	 * Return a new instance of this list based on reversing the current sort.
	 *
	 * @return SS_Sortable
	 * @example $list = $list->reverse();
	 */
	public function reverse();
}
