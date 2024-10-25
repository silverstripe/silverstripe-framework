<?php

namespace SilverStripe\Model\List;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * An interface that a class can implement to be treated as a list container.
 *
 * @template T
 * @extends ArrayAccess<array-key, T>
 * @extends IteratorAggregate<array-key, T>
 */
interface SS_List extends ArrayAccess, Countable, IteratorAggregate
{
    /**
     * Returns all the items in the list in an array.
     *
     * @return array<T>
     */
    public function toArray(): array;

    /**
     * Returns the contents of the list as an array of maps.
     */
    public function toNestedArray(): array;

    /**
     * Adds an item to the list, making no guarantees about where it will
     * appear.
     */
    public function add(mixed $item): void;

    /**
     * Removes an item from the list.
     *
     * Note that a return type is not specified on the interface as different impelementations
     * have different return types.
     */
    public function remove(mixed $item);

    /**
     * Returns the first item in the list.
     *
     * @return T|null
     */
    public function first(): mixed;

    /**
     * Returns the last item in the list.
     *
     * @return T|null
     */
    public function last(): mixed;

    /**
     * Returns a map of a key field to a value field of all the items in the
     * list.
     */
    public function map(string $keyfield = 'ID', string $titlefield = 'Title'): Map;

    /**
     * Returns the first item in the list where the key field is equal to the
     * value.
     *
     * @return T|null
     */
    public function find(string $key, mixed $value): mixed;

    /**
     * Returns an array of a single field value for all items in the list.
     */
    public function column(string $colName = "ID"): array;

    /**
     * Returns a unique array of a single field value for all items in the list.
     */
    public function columnUnique(string $colName = 'ID'): array;

    /**
     * Walks the list using the specified callback
     *
     * @return SS_List<T>
     */
    public function each(callable $callback): SS_List;

    /**
     * Returns TRUE if the list can be filtered by a given field expression.
     */
    public function canFilterBy(string $by): bool;

    /**
     * Returns true if this list has items
     */
    public function exists(): bool;

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
     * @return SS_List<T>
     */
    public function filter(...$args): SS_List;

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
     * @return SS_List<T>
     */
    public function filterAny(...$args): SS_List;

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
     * @return SS_List<T>
     */
    public function exclude(...$args): SS_List;

    /**
     * Return a copy of this list which does not contain any items with any of these params
     *
     * @return SS_List<T>
     */
    public function excludeAny(...$args): SS_List;

    /**
     * Return a new instance of this list that excludes any items with these characteristics
     * Filter this List by a callback function. The function will be passed each record of the List in turn,
     * and must return true for the record to be included. Returns the filtered list.
     *
     * @example $list = $list->filterByCallback(function($item, $list) { return $item->Age == 9; })
     * @return SS_List<T>
     */
    public function filterByCallback(callable $callback): SS_List;

    /**
     * Return the first item with the given ID
     *
     * @param int $id
     * @return T|null
     */
    public function byID(int|string|null $id): mixed;
    // Note that string ID's and null values should be handled as things like
    // form submissions and controller params will often be string IDs,
    // and null is often unintentionally passed in a lot of instances.
    // If this is changed in the future to only accept int, ensure that all other
    // filtering methods are updated at the same time for consistency

    /**
     * Filter this list to only contain the given Primary IDs
     *
     * @param array $ids Array of integers
     * @return SS_List<T>
     */
    public function byIDs(array $ids): SS_List;

    /**
     * Returns TRUE if the list can be sorted by a field.
     */
    public function canSortBy(string $by): bool;

    /**
     * Return a new instance of this list that is sorted by one or more fields. You can either pass in a single
     * field name and direction, or a map of field names to sort directions.
     *
     * @example $list = $list->sort('Name'); // default ASC sorting
     * @example $list = $list->sort('Name DESC'); // DESC sorting
     * @example $list = $list->sort('Name', 'ASC');
     * @example $list = $list->sort(array('Name'=>'ASC,'Age'=>'DESC'));
     *
     * @return SS_List<T>
     */
    public function sort(...$args): SS_List;


    /**
     * Return a new instance of this list based on reversing the current sort.
     *
     * @example $list = $list->reverse();
     *
     * @return SS_List<T>
     */
    public function reverse(): SS_List;

    /**
     * Returns a new instance of this list where no more than $limit records are included.
     * If $offset is specified, then that many records at the beginning of the list will be skipped.
     * This matches the behaviour of the SQL LIMIT clause.
     *
     * If `$length` is null, then no limit is applied. If `$length` is 0, then an empty list is returned.
     *
     * @throws InvalidArgumentException if $length or offset are negative
     * @return SS_List<T>
     */
    public function limit(?int $length, int $offset = 0): SS_List;
}
