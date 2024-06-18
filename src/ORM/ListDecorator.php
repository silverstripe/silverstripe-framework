<?php

namespace SilverStripe\ORM;

use SilverStripe\View\ViewableData;
use LogicException;
use Traversable;

/**
 * A base class for decorators that wrap around a list to provide additional
 * functionality. It passes through list methods to the underlying list
 * implementation.
 *
 * @template TList of SS_List&Sortable&Filterable&Limitable
 * @template T
 * @implements SS_List<T>
 * @implements Sortable<T>
 * @implements Filterable<T>
 * @implements Limitable<T>
 */
abstract class ListDecorator extends ViewableData implements SS_List, Sortable, Filterable, Limitable
{
    /**
     * @var TList<T>
     */
    protected SS_List&Sortable&Filterable&Limitable $list;

    /**
     * @param TList<T> $list
     */
    public function __construct(SS_List&Sortable&Filterable&Limitable $list)
    {
        $this->setList($list);

        parent::__construct();
    }

    /**
     * @return TList<T>
     */
    public function getList(): SS_List&Sortable&Filterable&Limitable
    {
        return $this->list;
    }

    /**
     * Set the list this decorator wraps around.
     *
     * Useful for keeping a decorator/paginated list configuration intact while modifying
     * the underlying list.
     *
     * @template TListA
     * @template TA
     * @param TListA<TA> $list
     * @return static<TListA, TA>
     */
    public function setList(SS_List&Sortable&Filterable&Limitable $list): ListDecorator
    {
        $this->list = $list;
        $this->failover = $this->list;
        return $this;
    }

    public function offsetExists(mixed $key): bool
    {
        return $this->list->offsetExists($key);
    }

    /**
     * @return T
     */
    public function offsetGet(mixed $key): mixed
    {
        return $this->list->offsetGet($key);
    }

    public function offsetSet(mixed $key, mixed $value): void
    {
        $this->list->offsetSet($key, $value);
    }

    public function offsetUnset(mixed $key): void
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

    /**
     * @return Traversable<T>
     */
    public function getIterator(): Traversable
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

    /**
     * @return int
     */
    public function TotalItems()
    {
        return $this->list->count();
    }

    public function Count(): int
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

    /**
     * @return TList<T>
     */
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
     *
     * @return TList<T>
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
     *
     * @return TList<T>
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
     *
     * @return TList<T>
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
     * @return ArrayList<T>
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

    /**
     * @return TList<T>
     */
    public function limit(?int $length, int $offset = 0): SS_List&Sortable&Filterable&Limitable
    {
        return $this->list->limit($length, $offset);
    }

    public function byID($id)
    {
        return $this->list->byID($id);
    }

    /**
     * Filter this list to only contain the given Primary IDs
     *
     * @param array $ids Array of integers
     *
     * @return TList<T>
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
     *
     * @return TList<T>
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
