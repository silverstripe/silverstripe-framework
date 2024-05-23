<?php

namespace SilverStripe\ORM;

use ArrayIterator;
use InvalidArgumentException;
use LogicException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\Filters\ExactMatchFilter;
use SilverStripe\ORM\Filters\SearchFilter;
use SilverStripe\ORM\Filters\SearchFilterable;
use SilverStripe\View\ArrayData;
use SilverStripe\View\ViewableData;
use Traversable;

/**
 * A list object that wraps around an array of objects or arrays.
 *
 * Note that (like DataLists), the implementations of the methods from SS_Filterable, SS_Sortable and
 * SS_Limitable return a new instance of ArrayList, rather than modifying the existing instance.
 *
 * For easy reference, methods that operate in this way are:
 *
 *   - limit
 *   - reverse
 *   - sort
 *   - filter
 *   - exclude
 *
 * @template T
 * @implements SS_List<T>
 * @implements Filterable<T>
 * @implements Sortable<T>
 * @implements Limitable<T>
 */
class ArrayList extends ViewableData implements SS_List, Filterable, Sortable, Limitable
{
    use SearchFilterable;

    /**
     * Whether filter and exclude calls should be case sensitive by default or not.
     * This configuration property is here for backwards compatability.
     *
     * @deprecated 5.1.0 use SearchFilter.default_case_sensitive instead
     */
    private static bool $default_case_sensitive = true;

    /**
     * Holds the items in the list
     *
     * @var array<array-key, T>
     */
    protected $items = [];

    /**
     * @param array<array-key, T> $items - an initial array to fill this object with
     */
    public function __construct(array $items = [])
    {
        $this->items = array_values($items ?? []);
        parent::__construct();
    }

    /**
     * Underlying type class for this list
     *
     * @var class-string<T>|null
     */
    protected $dataClass = null;

    /**
     * Return the class of items in this list, by looking at the first item inside it.
     *
     * @return class-string<T>|null
     */
    public function dataClass()
    {
        if ($this->dataClass) {
            return $this->dataClass;
        }
        if (count($this->items ?? []) > 0) {
            return get_class($this->items[0]);
        }
        return null;
    }

    /**
     * Hint this list to a specific type
     *
     * @param class-string<T> $class
     * @return $this
     */
    public function setDataClass($class)
    {
        $this->dataClass = $class;
        return $this;
    }

    /**
     * Return the number of items in this list
     *
     */
    public function count(): int
    {
        return count($this->items ?? []);
    }

    /**
     * Returns true if this list has items
     *
     * @return bool
     */
    public function exists()
    {
        return !empty($this->items);
    }

    /**
     * Returns an Iterator for this ArrayList.
     * This function allows you to use ArrayList in foreach loops
     *
     * @return Traversable<T>
     */
    public function getIterator(): Traversable
    {
        foreach ($this->items as $i => $item) {
            if (is_array($item)) {
                yield new ArrayData($item);
            } else {
                yield $item;
            }
        }
    }

    /**
     * Return an array of the actual items that this ArrayList contains.
     *
     * @return array<T>
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * Walks the list using the specified callback
     *
     * @param callable $callback
     * @return $this
     */
    public function each($callback)
    {
        foreach ($this as $item) {
            $callback($item);
        }
        return $this;
    }

    public function debug()
    {
        $val = "<h2>" . static::class . "</h2><ul>";
        foreach ($this->toNestedArray() as $item) {
            $val .= "<li style=\"list-style-type: disc; margin-left: 20px\">" . Debug::text($item) . "</li>";
        }
        $val .= "</ul>";
        return $val;
    }

    /**
     * Return this list as an array and every object it as an sub array as well
     */
    public function toNestedArray()
    {
        $result = [];

        foreach ($this->items as $item) {
            if (is_object($item)) {
                if (method_exists($item, 'toMap')) {
                    $result[] = $item->toMap();
                } else {
                    $result[] = (array) $item;
                }
            } else {
                $result[] = $item;
            }
        }

        return $result;
    }

    public function limit(?int $length, int $offset = 0): static
    {
        if ($length === null) {
            // If we unset the limit, we set the length to the size of the list. We still want the offset to be picked up
            $length = count($this->items);
        }

        if ($length < 0) {
            throw new InvalidArgumentException("\$length can not be negative. $length was provided.");
        }

        if ($offset < 0) {
            throw new InvalidArgumentException("\$offset can not be negative. $offset was provided.");
        }

        $list = clone $this;

        if ($length === 0) {
            // If we set the limit to 0, we return an empty list.
            $list->items = [];
        } else {
            $list->items = array_slice($this->items ?? [], $offset ?? 0, $length);
        }

        return $list;
    }

    /**
     * Add this $item into this list
     *
     * @param mixed $item
     */
    public function add($item)
    {
        $this->push($item);
    }

    /**
     * Remove this item from this list
     *
     * @param mixed $item
     */
    public function remove($item)
    {
        $renumberKeys = false;
        foreach ($this->items as $key => $value) {
            if ($item === $value) {
                $renumberKeys = true;
                unset($this->items[$key]);
            }
        }
        if ($renumberKeys) {
            $this->items = array_values($this->items ?? []);
        }
    }

    /**
     * Replaces an item in this list with another item.
     *
     * @param array|object $item
     * @param array|object $with
     * @return void
     */
    public function replace($item, $with)
    {
        foreach ($this->items as $key => $candidate) {
            if ($candidate === $item) {
                $this->items[$key] = $with;
                return;
            }
        }
    }

    /**
     * Merges with another array or list by pushing all the items in it onto the
     * end of this list.
     *
     * @param iterable $with
     */
    public function merge($with)
    {
        foreach ($with as $item) {
            $this->push($item);
        }
    }

    /**
     * Removes items from this list which have a duplicate value for a certain
     * field. This is especially useful when combining lists.
     *
     * @param string $field
     * @return $this
     */
    public function removeDuplicates($field = 'ID')
    {
        $seen = [];
        $renumberKeys = false;

        foreach ($this->items as $key => $item) {
            $value = $this->extractValue($item, $field);

            if (array_key_exists($value, $seen ?? [])) {
                $renumberKeys = true;
                unset($this->items[$key]);
            }

            $seen[$value] = true;
        }

        if ($renumberKeys) {
            $this->items = array_values($this->items ?? []);
        }

        return $this;
    }

    /**
     * Pushes an item onto the end of this list.
     *
     * @param array|object $item
     */
    public function push($item)
    {
        $this->items[] = $item;
    }

    /**
     * Pops the last element off the end of the list and returns it.
     *
     * @return array|object
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Add an item onto the beginning of the list.
     *
     * @param array|object $item
     */
    public function unshift($item)
    {
        array_unshift($this->items, $item);
    }

    /**
     * Shifts the item off the beginning of the list and returns it.
     *
     * @return array|object
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    public function first()
    {
        if (empty($this->items)) {
            return null;
        }

        return reset($this->items);
    }

    public function last()
    {
        if (empty($this->items)) {
            return null;
        }

        return end($this->items);
    }

    /**
     * Returns a map of this list
     *
     * @param string $keyfield The 'key' field of the result array
     * @param string $titlefield The value field of the result array
     * @return Map
     */
    public function map($keyfield = 'ID', $titlefield = 'Title')
    {
        $list = clone $this;
        return new Map($list, $keyfield, $titlefield);
    }

    /**
     * Returns an array of a single field value for all items in the list.
     *
     * @param string $colName
     * @return array
     */
    public function column($colName = 'ID')
    {
        $result = [];

        foreach ($this->items as $item) {
            $result[] = $this->extractValue($item, $colName);
        }

        return $result;
    }

    /**
     * Returns a unique array of a single field value for all the items in the list
     *
     * @param string $colName
     * @return array
     */
    public function columnUnique($colName = 'ID')
    {
        return array_unique($this->column($colName) ?? []);
    }

    /**
     * You can always sort a ArrayList
     *
     * @param string $by
     * @return bool
     */
    public function canSortBy($by)
    {
        return true;
    }

    /**
     * Reverses an {@link ArrayList}
     *
     * @return static<T>
     */
    public function reverse()
    {
        $list = clone $this;
        $list->items = array_reverse($this->items ?? []);

        return $list;
    }

    /**
     * Parses a specified column into a sort field and direction
     *
     * @param string $column String to parse containing the column name
     * @param mixed $direction Optional Additional argument which may contain the direction
     * @return array Sort specification in the form array("Column", SORT_ASC).
     */
    protected function parseSortColumn($column, $direction = null)
    {
        // Substitute the direction for the column if column is a numeric index
        if ($direction && (empty($column) || is_numeric($column))) {
            $column = $direction;
            $direction = null;
        }

        // Parse column specification, considering possible ansi sql quoting
        // Note that table prefix is allowed, but discarded
        if (preg_match('/^("?(?<table>[^"\s]+)"?\\.)?"?(?<column>[^"\s]+)"?(\s+(?<direction>((asc)|(desc))(ending)?))?$/i', $column ?? '', $match)) {
            $column = $match['column'];
            if (empty($direction) && !empty($match['direction'])) {
                $direction = $match['direction'];
            }
        } else {
            throw new InvalidArgumentException("Invalid sort() column");
        }

        // Parse sort direction specification
        if (empty($direction) || preg_match('/^asc(ending)?$/i', $direction ?? '')) {
            $direction = SORT_ASC;
        } elseif (preg_match('/^desc(ending)?$/i', $direction ?? '')) {
            $direction = SORT_DESC;
        } else {
            throw new InvalidArgumentException("Invalid sort() direction");
        }

        return [$column, $direction];
    }

    /**
     * Sorts this list by one or more fields. You can either pass in a single
     * field name and direction, or a map of field names to sort directions.
     *
     * Note that columns may be double quoted as per ANSI sql standard
     *
     * @see SS_List::sort()
     * @example $list->sort('Name'); // default ASC sorting
     * @example $list->sort('Name DESC'); // DESC sorting
     * @example $list->sort('Name', 'ASC');
     * @example $list->sort(array('Name'=>'ASC,'Age'=>'DESC'));
     *
     * @return static<T>
     */
    public function sort()
    {
        $args = func_get_args();

        if (count($args ?? [])==0) {
            return $this;
        }
        if (count($args ?? [])>2) {
            throw new InvalidArgumentException('This method takes zero, one or two arguments');
        }
        $columnsToSort = [];

        // One argument and it's a string
        if (count($args ?? [])==1 && is_string($args[0])) {
            list($column, $direction) = $this->parseSortColumn($args[0]);
            $columnsToSort[$column] = $direction;
        } elseif (count($args ?? [])==2) {
            list($column, $direction) = $this->parseSortColumn($args[0], $args[1]);
            $columnsToSort[$column] = $direction;
        } elseif (is_array($args[0])) {
            foreach ($args[0] as $key => $value) {
                list($column, $direction) = $this->parseSortColumn($key, $value);
                $columnsToSort[$column] = $direction;
            }
        } else {
            throw new InvalidArgumentException("Bad arguments passed to sort()");
        }

        // Store the original keys of the items as a sort fallback, so we can preserve the original order in the event
        // that array_multisort is unable to work out a sort order for them. This also prevents array_multisort trying
        // to inspect object properties which can result in errors with circular dependencies
        $originalKeys = array_keys($this->items ?? []);

        // This the main sorting algorithm that supports infinite sorting params
        $multisortArgs = [];
        $values = [];
        $firstRun = true;
        foreach ($columnsToSort as $column => $direction) {
            // The reason these are added to columns is of the references, otherwise when the foreach
            // is done, all $values and $direction look the same
            $values[$column] = [];
            $sortDirection[$column] = $direction;
            // We need to subtract every value into a temporary array for sorting
            foreach ($this->items as $index => $item) {
                $values[$column][] = strtolower($this->extractValue($item, $column) ?? '');
            }
            // PHP 5.3 requires below arguments to be reference when using array_multisort together
            // with call_user_func_array
            // First argument is the 'value' array to be sorted
            $multisortArgs[] = &$values[$column];
            // First argument is the direction to be sorted,
            $multisortArgs[] = &$sortDirection[$column];
            if ($firstRun) {
                $multisortArgs[] = SORT_REGULAR;
            }
            $firstRun = false;
        }

        $multisortArgs[] = &$originalKeys;

        $list = clone $this;
        // As the last argument we pass in a reference to the items that all the sorting will be applied upon
        $multisortArgs[] = &$list->items;
        call_user_func_array('array_multisort', $multisortArgs ?? []);
        return $list;
    }

    /**
     * Shuffle the items in this array list
     *
     * @return $this
     */
    public function shuffle()
    {
        shuffle($this->items);

        return $this;
    }

    /**
     * Returns true if the given column can be used to filter the records.
     *
     * It works by checking the fields available in the first record of the list.
     *
     * @param string $by
     * @return bool
     */
    public function canFilterBy($by)
    {
        if (empty($this->items)) {
            return false;
        }

        $firstRecord = $this->first();

        if (is_array($firstRecord)) {
            return array_key_exists($by, $firstRecord);
        }

        if ($firstRecord instanceof ViewableData) {
            return $firstRecord->hasField($by);
        }

        return property_exists($firstRecord, $by ?? '');
    }

    /**
     * Find the first item of this list where the given key = value
     *
     * @param string $key
     * @param mixed $value
     * @return T|null
     */
    public function find($key, $value)
    {
        return $this->filter($key, $value)->first();
    }

    /**
     * Filter the list to include items with these characteristics
     *
     * @see Filterable::filter()
     * @example $list->filter('Name', 'bob'); // only bob in the list
     * @example $list->filter('Name', array('aziz', 'bob'); // aziz and bob in list
     * @example $list->filter(array('Name'=>'bob, 'Age'=>21)); // bob with the Age 21 in list
     * @example $list->filter(array('Name'=>'bob, 'Age'=>array(21, 43))); // bob with the Age 21 or 43
     * @example $list->filter(array('Name'=>array('aziz','bob'), 'Age'=>array(21, 43)));
     *          // aziz with the age 21 or 43 and bob with the Age 21 or 43
     *
     * Also supports SearchFilter syntax
     * @example // include anyone with "sam" anywhere in their name
     *          $list = $list->filter('Name:PartialMatch', 'sam');
     *
     * @return static<T>
     */
    public function filter()
    {
        $filters = $this->normaliseFilterArgs(...func_get_args());
        return $this->filterOrExclude($filters);
    }

    /**
     * Return a copy of this list which contains items matching any of these characteristics.
     *
     * @example // only bob in the list
     *          $list = $list->filterAny('Name', 'bob');
     * @example // azis or bob in the list
     *          $list = $list->filterAny('Name', array('aziz', 'bob');
     * @example // bob or anyone aged 21 in the list
     *          $list = $list->filterAny(array('Name'=>'bob, 'Age'=>21));
     * @example // bob or anyone aged 21 or 43 in the list
     *          $list = $list->filterAny(array('Name'=>'bob, 'Age'=>array(21, 43)));
     * @example // all bobs, phils or anyone aged 21 or 43 in the list
     *          $list = $list->filterAny(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43)));
     *
     * Also supports SearchFilter syntax
     * @example // include anyone with "sam" anywhere in their name
     *          $list = $list->filterAny('Name:PartialMatch', 'sam');
     *
     * @param string|array See {@link filter()}
     * @return static<T>
     */
    public function filterAny()
    {
        $filters = $this->normaliseFilterArgs(...func_get_args());
        return $this->filterOrExclude($filters, true, true);
    }

    /**
     * Exclude the list to not contain items with these characteristics
     *
     * @see SS_List::exclude()
     * @example $list->exclude('Name', 'bob'); // exclude bob from list
     * @example $list->exclude('Name', array('aziz', 'bob'); // exclude aziz and bob from list
     * @example $list->exclude(array('Name'=>'bob, 'Age'=>21)); // exclude bob that has Age 21
     * @example $list->exclude(array('Name'=>'bob, 'Age'=>array(21, 43))); // exclude bob with Age 21 or 43
     * @example $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43)));
     *          // bob age 21 or 43, phil age 21 or 43 would be excluded
     *
     * Also supports SearchFilter syntax
     * @example // everyone except anyone with "sam" anywhere in their name
     *          $list = $list->exclude('Name:PartialMatch', 'sam');
     *
     * @return static<T>
     */
    public function exclude()
    {
        $filters = $this->normaliseFilterArgs(...func_get_args());
        return $this->filterOrExclude($filters, false);
    }

    /**
     * Return a copy of the list excluding any items that have any of these characteristics
     *
     * @example // everyone except bob in the list
     *          $list = $list->excludeAny('Name', 'bob');
     * @example // everyone except azis or bob in the list
     *          $list = $list->excludeAny('Name', array('aziz', 'bob');
     * @example // everyone except bob or anyone aged 21 in the list
     *          $list = $list->excludeAny(array('Name'=>'bob, 'Age'=>21));
     * @example // everyone except bob or anyone aged 21 or 43 in the list
     *          $list = $list->excludeAny(array('Name'=>'bob, 'Age'=>array(21, 43)));
     * @example // everyone except all bobs, phils or anyone aged 21 or 43 in the list
     *          $list = $list->excludeAny(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43)));
     *
     * Also supports SearchFilter syntax
     * @example // everyone except anyone with "sam" anywhere in their name
     *          $list = $list->excludeAny('Name:PartialMatch', 'sam');
     *
     * @param string|array See {@link filter()}
     * @return static<T>
     */
    public function excludeAny(): static
    {
        $filters = $this->normaliseFilterArgs(...func_get_args());
        return $this->filterOrExclude($filters, false, true);
    }

    /**
     * Apply the appropriate filtering or excluding
     * @return static<T>
     */
    protected function filterOrExclude(array $filters, bool $inclusive = true, bool $any = false): static
    {
        $itemsToKeep = [];
        $searchFilters = [];
        $hasNullFilter = false;

        foreach ($filters as $filterKey => $filterValue) {
            // Check if we have any null filter values for backwards compatability, since nulls are treated specially
            // in the ExactMatchFilter
            if (is_array($filterValue)) {
                foreach ($filterValue as $value) {
                    if ($value === null) {
                        $hasNullFilter = true;
                    }
                }
            } elseif ($filterValue === null) {
                $hasNullFilter = true;
            }
            $searchFilter = $this->createSearchFilter($filterKey, $filterValue);

            // Apply default case sensitivity for backwards compatability
            if (!str_contains($filterKey, ':case') && !str_contains($filterKey, ':nocase')) {
                $caseSensitive = Deprecation::withNoReplacement(fn() => static::config()->get('default_case_sensitive'));
                if ($caseSensitive && in_array('case', $searchFilter->getSupportedModifiers())) {
                    $searchFilter->setModifiers($searchFilter->getModifiers() + ['case']);
                } elseif (!$caseSensitive && in_array('nocase', $searchFilter->getSupportedModifiers())) {
                    $searchFilter->setModifiers($searchFilter->getModifiers() + ['nocase']);
                }
            }

            $searchFilters[$filterKey] = $searchFilter;
        }

        foreach ($this->items as $item) {
            $matches = [];
            foreach ($filters as $filterKey => $filterValue) {
                $searchFilter = $searchFilters[$filterKey];
                $extractedValue = $this->extractValue($item, $searchFilter->getFullName());
                $hasMatch = null;

                // If we need to do a legacy null comparison, try that first.
                if (($searchFilter instanceof ExactMatchFilter) && ($hasNullFilter || $extractedValue === null)) {
                    $hasMatch = $this->performLegacyNullMatch($extractedValue, $filterValue);
                    if ($hasMatch !== null && in_array('not', $searchFilter->getModifiers())) {
                        $hasMatch = !$hasMatch;
                    }
                }

                // If the null comparison wasn't necessary or was incomplete, let searchfilters do the work.
                if ($hasMatch === null) {
                    $hasMatch = $searchFilter->matches($extractedValue);
                }


                $matches[$hasMatch] = 1;
                // If this is excludeAny or filterAny and we have a match, we can stop looking for matches.
                if ($any && $hasMatch) {
                    break;
                }
            }

            // filterAny or excludeAny allow any true value to be a match; filter or exclude require any false value
            // to be a mismatch.
            $isMatch = $any ? isset($matches[true]) : !isset($matches[false]);

            // If inclusive (filter) and we have a match, or exclusive (exclude) and there is NO match, keep the item.
            if (($inclusive && $isMatch) || (!$inclusive && !$isMatch)) {
                $itemsToKeep[] = $item;
            }
        }

        $list = clone $this;
        $list->items = $itemsToKeep;
        return $list;
    }

    /**
     * Required for backwards compatibility since ExactMatch handles null values differently than ArrayList used to.
     */
    private function performLegacyNullMatch(mixed $objectValue, mixed $filterValues): ?bool
    {
        if (!is_array($filterValues)) {
            $filterValues = [$filterValues];
        }
        foreach ($filterValues as $filterValue) {
            // Skip comparisons between two non-null values, we can trust searchfilter for those.
            if ($objectValue !== null && $filterValue !== null) {
                continue;
            }
            // This is the legacy comparison.
            if ($filterValue == $objectValue) {
                return true;
            }
        }
        return $objectValue === null ? false : null;
    }

    /**
     * Take the "standard" arguments that the filter/exclude functions take and return a single array with
     * 'colum' => 'value'
     *
     * @param $column array|string The column name to filter OR an assosicative array of column => value
     * @param $value array|string|null The values to filter the $column against
     *
     * @return array The normalised keyed array
     */
    protected function normaliseFilterArgs($column, $value = null)
    {
        $args = func_get_args();
        if (count($args ?? []) > 2) {
            throw new InvalidArgumentException('filter takes one array or two arguments');
        }

        if (count($args ?? []) === 1 && !is_array($args[0])) {
            throw new InvalidArgumentException('filter takes one array or two arguments');
        }

        $keepUs = [];
        if (count($args ?? []) === 2) {
            $keepUs[$args[0]] = $args[1];
        }

        if (count($args ?? []) === 1 && is_array($args[0])) {
            foreach ($args[0] as $key => $val) {
                $keepUs[$key] = $val;
            }
        }

        return $keepUs;
    }

    /**
     * Filter this list to only contain the given Primary IDs
     *
     * @param array $ids Array of integers, will be automatically cast/escaped.
     *
     * @return static<T>
     */
    public function byIDs($ids)
    {
        $ids = array_map('intval', $ids ?? []); // sanitize
        return $this->filter('ID', $ids);
    }

    public function byID($id)
    {
        $firstElement = $this->filter("ID", $id)->first();

        if ($firstElement === false) {
            return null;
        }

        return $firstElement;
    }

    /**
     * @see Filterable::filterByCallback()
     *
     * @example $list = $list->filterByCallback(function($item, $list) { return $item->Age == 9; })
     * @param callable $callback
     * @return static<T>
     */
    public function filterByCallback($callback)
    {
        if (!is_callable($callback)) {
            throw new LogicException(sprintf(
                "SS_Filterable::filterByCallback() passed callback must be callable, '%s' given",
                gettype($callback)
            ));
        }

        $output = static::create();

        foreach ($this as $item) {
            if (call_user_func($callback, $item, $this)) {
                $output->push($item);
            }
        }

        return $output;
    }

    protected function shouldExclude($item, $args)
    {
    }


    /**
     * Returns whether an item with $key exists
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->items ?? []);
    }

    /**
     * Returns item stored in list with index $key
     * @return T|null
     */
    public function offsetGet(mixed $offset): mixed
    {
        if ($this->offsetExists($offset)) {
            return $this->items[$offset];
        }
        return null;
    }

    /**
     * Set an item with the key in $key
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Unset an item with the key in $key
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * Extracts a value from an item in the list, where the item is either an
     * object or array.
     *
     * @param array|object $item
     * @param string $key
     * @return mixed
     */
    protected function extractValue($item, $key)
    {
        if (is_object($item)) {
            if (method_exists($item, 'hasMethod') && $item->hasMethod($key)) {
                return $item->{$key}();
            }
            return $item->{$key};
        }

        if (array_key_exists($key, $item ?? [])) {
            return $item[$key];
        }

        return null;
    }
}
