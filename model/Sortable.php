<?php

/**
 * Additional interface for {@link SS_List} classes that are sortable.
 * 
 * @see SS_List, SS_Filterable, SS_Limitable
 */
interface SS_Sortable {

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
	
}
