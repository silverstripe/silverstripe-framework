<?php

namespace SilverStripe\Model\List;

use SilverStripe\Model\ModelData;
use LogicException;
use Traversable;
use BadMethodCallException;

/**
 * A base class for decorators that wrap around a list to provide additional
 * functionality. It passes through list methods to the underlying list
 * implementation.
 *
 * @template TList of SS_List
 * @template T
 * @implements SS_List<T>
 */
abstract class ListDecorator extends ModelData implements SS_List
{
    /**
     * @var TList<T>
     */
    protected SS_List $list;

    /**
     * @param TList<T> $list
     */
    public function __construct(SS_List $list)
    {
        $this->setList($list);
        parent::__construct();
    }

    /**
     * @return TList<T>
     */
    public function getList(): SS_List
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
    public function setList(SS_List $list): ListDecorator
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

    public function toArray(): array
    {
        return $this->list->toArray();
    }

    public function toNestedArray(): array
    {
        return $this->list->toNestedArray();
    }

    public function add(mixed $item): void
    {
        $this->list->add($item);
    }

    public function remove(mixed $itemObject)
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

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function first(): mixed
    {
        return $this->list->first();
    }

    public function last(): mixed
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

    public function count(): int
    {
        return $this->list->count();
    }

    public function forTemplate(): string
    {
        if (method_exists($this->list, 'forTemplate')) {
            return call_user_func([$this->list, 'forTemplate']);
        }
        throw new BadMethodCallException(sprintf(
            "Method 'forTemplate' not found on class '%s'",
            get_class($this->list)
        ));
    }

    public function map(string $index = 'ID', string $titleField = 'Title'): Map
    {
        return $this->list->map($index, $titleField);
    }

    public function find(string $key, mixed $value): mixed
    {
        return $this->list->find($key, $value);
    }

    public function column(string $value = 'ID'): array
    {
        return $this->list->column($value);
    }

    public function columnUnique(string $value = "ID"): array
    {
        if (method_exists($this->list, 'columnUnique')) {
            return call_user_func([$this->list, 'columnUnique'], $value);
        }
        throw new BadMethodCallException(sprintf(
            "Method 'columnUnique' not found on class '%s'",
            get_class($this->list)
        ));
    }

    /**
     * @return TList<T>
     */
    public function each(callable $callback): SS_List
    {
        return $this->list->each($callback);
    }

    public function canSortBy(string $by): bool
    {
        return $this->list->canSortBy($by);
    }

    public function reverse(): SS_List
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
    public function sort(...$args): SS_List
    {
        return $this->list->sort(...$args);
    }

    public function canFilterBy(string $by): bool
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
    public function filter(...$args): SS_List
    {
        return $this->list->filter(...$args);
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
    public function filterAny(...$args): SS_List
    {
        return $this->list->filterAny(...$args);
    }

    /**
     * Note that, in the current implementation, the filtered list will be an ArrayList, but this may change in a
     * future implementation.
     * @see SS_List::filterByCallback()
     *
     * @example $list = $list->filterByCallback(function($item, $list) { return $item->Age == 9; })
     * @return SS_List<T>
     */
    public function filterByCallback(callable $callback): SS_List
    {
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
    public function limit(?int $length, int $offset = 0): SS_List
    {
        return $this->list->limit($length, $offset);
    }

    public function byID(int|string|null $id): mixed
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
    public function byIDs(array $ids): SS_List
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
    public function exclude(...$args): SS_List
    {
        return $this->list->exclude(...$args);
    }

    /**
     * Return a copy of this list which does not contain any items with any of these params
     *
     * @example $list = $list->excludeAny('Name', 'bob'); // exclude bob from list
     * @example $list = $list->excludeAny('Name', array('aziz', 'bob'); // exclude aziz and bob from list
     * @example $list = $list->excludeAny(array('Name'=>'bob, 'Age'=>21)); // exclude bob or Age 21
     * @example $list = $list->excludeAny(array('Name'=>'bob, 'Age'=>array(21, 43))); // exclude bob or Age 21 or 43
     * @example $list = $list->excludeAny(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43)));
     *          // bob, phil, 21 or 43 would be excluded
     *
     * @return TList<T>
     */
    public function excludeAny(...$args): SS_List
    {
        return $this->list->excludeAny(...$args);
    }

    public function debug(): string
    {
        if (method_exists($this->list, 'debug')) {
            return call_user_func([$this->list, 'debug']);
        }
        throw new BadMethodCallException(sprintf(
            "Method 'debug' not found on class '%s'",
            get_class($this->list)
        ));
    }
}
