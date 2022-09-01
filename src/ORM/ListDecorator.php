<?php

namespace SilverStripe\ORM;

use SilverStripe\View\ViewableData;
use LogicException;

/**
 * A base class for decorators that wrap around a list to provide additional
 * functionality. It passes through list methods to the underlying list
 * implementation.
 */
abstract class ListDecorator extends ViewableData implements SS_List, Sortable, Filterable, Limitable
{

    /**
     * @var SS_List
     */
    protected $list;

    public function __construct(SS_List $list)
    {
        $this->setList($list);

        parent::__construct();
    }

    /**
     * Returns the list this decorator wraps around.
     *
     * @return SS_List
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * Set the list this decorator wraps around.
     *
     * Useful for keeping a decorator/paginated list configuration intact while modifying
     * the underlying list.
     *
     * @return SS_List
     */
    public function setList($list)
    {
        $this->list = $list;
        $this->failover = $this->list;
        return $this;
    }

    // PROXIED METHODS ---------------------------------------------------------

    #[\ReturnTypeWillChange]
    public function offsetExists($key)
    {
        return $this->list->offsetExists($key);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        return $this->list->offsetGet($key);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value)
    {
        $this->list->offsetSet($key, $value);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($key)
    {
        $this->list->offsetUnset($key);
    }

    public function toArray()
    {
        return $this->list->toArray();
    }

    public function toNestedArray()
    {
        return $this->list->toNestedArray();
    }

    public function add($item)
    {
        $this->list->add($item);
    }

    public function remove($itemObject)
    {
        $this->list->remove($itemObject);
    }

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return $this->list->getIterator();
    }

    public function exists()
    {
        return $this->list->exists();
    }

    public function first()
    {
        return $this->list->first();
    }

    public function last()
    {
        return $this->list->last();
    }

    public function TotalItems()
    {
        return $this->list->count();
    }

    #[\ReturnTypeWillChange]
    public function Count()
    {
        return $this->list->count();
    }

    public function forTemplate()
    {
        return $this->list->forTemplate();
    }

    public function map($index = 'ID', $titleField = 'Title')
    {
        return $this->list->map($index, $titleField);
    }

    public function find($key, $value)
    {
        return $this->list->find($key, $value);
    }

    public function column($value = 'ID')
    {
        return $this->list->column($value);
    }

    public function columnUnique($value = "ID")
    {
        return $this->list->columnUnique($value);
    }

    public function each($callback)
    {
        return $this->list->each($callback);
    }

    public function canSortBy($by)
    {
        return $this->list->canSortBy($by);
    }

    public function reverse()
    {
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
    public function sort()
    {
        return $this->list->sort(...func_get_args());
    }

    public function canFilterBy($by)
    {
        return $this->list->canFilterBy($by);
    }

    /**
     * Filter the list to include items with these characteristics
     *
     * @example $list->filter('Name', 'bob'); // only bob in list
     * @example $list->filter('Name', array('aziz', 'bob'); // aziz and bob in list
     * @example $list->filter(array('Name'=>'bob, 'Age'=>21)); // bob or someone with Age 21
     * @example $list->filter(array('Name'=>'bob, 'Age'=>array(21, 43))); // bob or anyone with Age 21 or 43
     */
    public function filter()
    {
        return $this->list->filter(...func_get_args());
    }

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
     * @return DataList
     */
    public function filterAny()
    {
        return $this->list->filterAny(...func_get_args());
    }

    /**
     * Note that, in the current implementation, the filtered list will be an ArrayList, but this may change in a
     * future implementation.
     * @see Filterable::filterByCallback()
     *
     * @example $list = $list->filterByCallback(function($item, $list) { return $item->Age == 9; })
     * @param callable $callback
     * @return ArrayList (this may change in future implementations)
     */
    public function filterByCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new LogicException(sprintf(
                "SS_Filterable::filterByCallback() passed callback must be callable, '%s' given",
                gettype($callback)
            ));
        }
        $output = ArrayList::create();
        foreach ($this->list as $item) {
            if ($callback($item, $this->list)) {
                $output->push($item);
            }
        }
        return $output;
    }

    public function limit($limit, $offset = 0)
    {
        return $this->list->limit($limit, $offset);
    }

    /**
     * Return the first item with the given ID
     *
     * @param int $id
     * @return mixed
     */
    public function byID($id)
    {
        return $this->list->byID($id);
    }

    /**
     * Filter this list to only contain the given Primary IDs
     *
     * @param array $ids Array of integers
     * @return SS_List
     */
    public function byIDs($ids)
    {
        return $this->list->byIDs($ids);
    }

    /**
     * Exclude the list to not contain items with these characteristics
     *
     * @example $list->exclude('Name', 'bob'); // exclude bob from list
     * @example $list->exclude('Name', array('aziz', 'bob'); // exclude aziz and bob from list
     * @example $list->exclude(array('Name'=>'bob, 'Age'=>21)); // exclude bob or someone with Age 21
     * @example $list->exclude(array('Name'=>'bob, 'Age'=>array(21, 43))); // exclude bob or anyone with Age 21 or 43
     */
    public function exclude()
    {
        return $this->list->exclude(...func_get_args());
    }

    public function debug()
    {
        return $this->list->debug();
    }
}
