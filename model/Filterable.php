<?php

/**
 * Additional interface for {@link SS_List} classes that are filterable.
 *
 * All methods in this interface are immutable - they should return new instances with the filter
 * applied, rather than applying the filter in place
 *
 * @see SS_List, SS_Sortable, SS_Limitable
 * @package framework
 * @subpackage model
 */
interface SS_Filterable extends SS_List {

	/**
	 * Returns TRUE if the list can be filtered by a given field expression.
	 *
	 * @param  string $by
	 * @return bool
	 */
	public function canFilterBy($by);

	/**
	 * Return a new instance of this list that only includes items with these charactaristics
	 *
	 * @return SS_Filterable
	 * @example $list = $list->filter('Name', 'bob'); // only bob in the list
	 * @example $list = $list->filter('Name', array('aziz', 'bob'); // aziz and bob in list
	 * @example $list = $list->filter(array('Name'=>'bob, 'Age'=>21)); // bob with the age 21
	 * @example $list = $list->filter(array('Name'=>'bob, 'Age'=>array(21, 43))); // bob with the Age 21 or 43
	 * @example $list = $list->filter(array('Name'=>array('aziz','bob'), 'Age'=>array(21, 43)));
	 *          // aziz with the age 21 or 43 and bob with the Age 21 or 43
	 */
	public function filter();

	/**
	 * Return a new instance of this list that excludes any items with these charactaristics
	 *
	 * @return SS_Filterable
	 * @example $list = $list->exclude('Name', 'bob'); // exclude bob from list
	 * @example $list = $list->exclude('Name', array('aziz', 'bob'); // exclude aziz and bob from list
	 * @example $list = $list->exclude(array('Name'=>'bob, 'Age'=>21)); // exclude bob that has Age 21
	 * @example $list = $list->exclude(array('Name'=>'bob, 'Age'=>array(21, 43))); // exclude bob with Age 21 or 43
	 * @example $list = $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43)));
	 *          // bob age 21 or 43, phil age 21 or 43 would be excluded
	 */
	public function exclude();

	/**
	 * Return a new instance of this list that excludes any items with these charactaristics
	 * Filter this List by a callback function. The function will be passed each record of the List in turn,
	 * and must return true for the record to be included. Returns the filtered list.
	 *
	 * @example $list = $list->filterByCallback(function($item, $list) { return $item->Age == 9; })
	 * @return SS_Filterable
	 */
	public function filterByCallback($callback);
}
