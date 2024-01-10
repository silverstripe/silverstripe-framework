<?php

namespace SilverStripe\ORM;

/**
 * Additional interface for {@link SS_List} classes that are filterable.
 *
 * All methods in this interface are immutable - they should return new instances with the filter
 * applied, rather than applying the filter in place
 *
 * @see SS_List
 * @see Sortable
 * @see Limitable
 *
 * @template T
 * @extends SS_List<T>
 */
interface Filterable extends SS_List
{

    /**
     * Returns TRUE if the list can be filtered by a given field expression.
     *
     * @param  string $by
     * @return bool
     */
    public function canFilterBy($by);

    /**
     * Return a new instance of this list that only includes items with these characteristics
     *
     * @example $list = $list->filter('Name', 'bob'); // only bob in the list
     * @example $list = $list->filter('Name', array('aziz', 'bob'); // aziz and bob in list
     * @example $list = $list->filter(array('Name'=>'bob, 'Age'=>21)); // bob with the age 21
     * @example $list = $list->filter(array('Name'=>'bob, 'Age'=>array(21, 43))); // bob with the Age 21 or 43
     * @example $list = $list->filter(array('Name'=>array('aziz','bob'), 'Age'=>array(21, 43)));
     *          // aziz with the age 21 or 43 and bob with the Age 21 or 43
     *
     * @return static<T>
     */
    public function filter();

    /**
     * Return a copy of this list which contains items matching any of these characteristics.
     *
     * @example // only bob in the list
     *          $list = $list->filterAny('Name', 'bob');
     *          // SQL: WHERE "Name" = 'bob'
     * @example // azis or bob in the list
     *          $list = $list->filterAny('Name', array('aziz', 'bob');
     *          // SQL: WHERE ("Name" IN ('aziz','bob'))
     * @example // bob or anyone aged 21 in the list
     *          $list = $list->filterAny(array('Name'=>'bob, 'Age'=>21));
     *          // SQL: WHERE ("Name" = 'bob' OR "Age" = '21')
     * @example // bob or anyone aged 21 or 43 in the list
     *          $list = $list->filterAny(array('Name'=>'bob, 'Age'=>array(21, 43)));
     *          // SQL: WHERE ("Name" = 'bob' OR ("Age" IN ('21', '43'))
     * @example // all bobs, phils or anyone aged 21 or 43 in the list
     *          $list = $list->filterAny(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43)));
     *          // SQL: WHERE (("Name" IN ('bob', 'phil')) OR ("Age" IN ('21', '43'))
     *
     * @param string|array See {@link filter()}
     * @return static<T>
     */
    public function filterAny();

    /**
     * Return a new instance of this list that excludes any items with these characteristics
     *
     * @example $list = $list->exclude('Name', 'bob'); // exclude bob from list
     * @example $list = $list->exclude('Name', array('aziz', 'bob'); // exclude aziz and bob from list
     * @example $list = $list->exclude(array('Name'=>'bob, 'Age'=>21)); // exclude bob that has Age 21
     * @example $list = $list->exclude(array('Name'=>'bob, 'Age'=>array(21, 43))); // exclude bob with Age 21 or 43
     * @example $list = $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43)));
     *          // bob age 21 or 43, phil age 21 or 43 would be excluded
     *
     * @return static<T>
     */
    public function exclude();

    /**
     * Return a new instance of this list that excludes any items with these characteristics
     * Filter this List by a callback function. The function will be passed each record of the List in turn,
     * and must return true for the record to be included. Returns the filtered list.
     *
     * @example $list = $list->filterByCallback(function($item, $list) { return $item->Age == 9; })
     * @param callable $callback
     * @return SS_List<T>
     */
    public function filterByCallback($callback);

    /**
     * Return the first item with the given ID
     *
     * @param int $id
     * @return T|null
     */
    public function byID($id);

    /**
     * Filter this list to only contain the given Primary IDs
     *
     * @param array $ids Array of integers
     * @return static<T>
     */
    public function byIDs($ids);
}
