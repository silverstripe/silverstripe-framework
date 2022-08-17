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

    public function __construct(SS_List $list): void
    {
        $this->setList($list);

        parent::__construct();
    }

    /**
     * Returns the list this decorator wraps around.
     *
     * @return SS_List
     */
    public function getList(): Mock_ArrayList_198b46e5
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
    public function setList(SilverStripe\ORM\DataList $list): SilverStripe\ORM\PaginatedList
    {
        $this->list = $list;
        $this->failover = $this->list;
        return $this;
    }

    // PROXIED METHODS ---------------------------------------------------------

    #[\ReturnTypeWillChange]
    public function offsetExists(string $key): string
    {
        return $this->list->offsetExists($key);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet(int $key): string
    {
        return $this->list->offsetGet($key);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet(string $key, string $value): void
    {
        $this->list->offsetSet($key, $value);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset(string $key): void
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

    public function add(string $item): void
    {
        $this->list->add($item);
    }

    public function remove(SilverStripe\CMS\Model\SiteTree|string $itemObject): void
    {
        $this->list->remove($itemObject);
    }

    #[\ReturnTypeWillChange]
    public function getIterator(): string
    {
        return $this->list->getIterator();
    }

    public function exists(): bool
    {
        return $this->list->exists();
    }

    public function first(): SilverStripe\CMS\Model\SiteTree|int
    {
        return $this->list->first();
    }

    public function last(): int
    {
        return $this->list->last();
    }

    public function TotalItems(): int
    {
        return $this->list->count();
    }

    #[\ReturnTypeWillChange]
    public function Count(): int
    {
        return $this->list->count();
    }

    public function forTemplate()
    {
        return $this->list->forTemplate();
    }

    public function map(string $index = 'ID', string $titleField = 'Title'): string
    {
        return $this->list->map($index, $titleField);
    }

    public function find(string $key, string $value): string
    {
        return $this->list->find($key, $value);
    }

    public function column(string $value = 'ID'): array|string
    {
        return $this->list->column($value);
    }

    public function columnUnique(string $value = "ID"): string
    {
        return $this->list->columnUnique($value);
    }

    public function each(callable $callback): string
    {
        return $this->list->each($callback);
    }

    public function canSortBy(string $by): bool
    {
        return $this->list->canSortBy($by);
    }

    public function reverse(): string
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
    public function sort(): SilverStripe\ORM\DataList|string
    {
        return $this->list->sort(...func_get_args());
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
     */
    public function filter(): string
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
    public function filterAny(): string
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
    public function filterByCallback(bool|callable $callback): SilverStripe\ORM\ArrayList
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

    public function limit(int $limit, int $offset = 0): string
    {
        return $this->list->limit($limit, $offset);
    }

    /**
     * Return the first item with the given ID
     *
     * @param int $id
     * @return mixed
     */
    public function byID(int $id): string
    {
        return $this->list->byID($id);
    }

    /**
     * Filter this list to only contain the given Primary IDs
     *
     * @param array $ids Array of integers
     * @return SS_List
     */
    public function byIDs(array $ids): string
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
    public function exclude(): string
    {
        return $this->list->exclude(...func_get_args());
    }

    public function debug(): string
    {
        return $this->list->debug();
    }
}
