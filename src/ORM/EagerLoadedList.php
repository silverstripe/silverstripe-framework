<?php

namespace SilverStripe\ORM;

use SilverStripe\Dev\Debug;
use SilverStripe\View\ViewableData;
use SilverStripe\ORM\Connect\DatabaseException;
use SilverStripe\ORM\FieldType\DBField;
use BadMethodCallException;
use InvalidArgumentException;
use LogicException;
use SilverStripe\ORM\Filters\SearchFilterable;
use Traversable;

/**
 * Represents an "eager loaded" DataList - i.e. the data has already been fetched from the database
 * for these records and likely for some of their relations.
 *
 * This list is designed to be plug-and-play with the various DataList implementations, with the exception
 * that because it doesn't make a database query to get its data, some methods are intentionally not implemented.
 *
 * Note that when this list represents a relation, adding items to or removing items from this list will NOT
 * affect the underlying relation in the database. This list is read-only.
 *
 * @template T of DataObject
 * @implements Relation<T>
 * @implements SS_List<T>
 * @implements Filterable<T>
 * @implements Sortable<T>
 * @implements Limitable<T>
 */
class EagerLoadedList extends ViewableData implements Relation, SS_List, Filterable, Sortable, Limitable
{
    use SearchFilterable;

    /**
     * List responsible for instantiating the actual DataObject objects from eager-loaded data
     * @var DataList<T>
     */
    private DataList $dataList;

    /**
     * Underlying DataObject class for this list
     * @var class-string<T>
     */
    private string $dataClass;

    /**
     * The ID(s) of the record that owns this list if the list represents a relation
     * Used for aggregations
     */
    private int|array|null $foreignID;

    /**
     * ID-indexed array that holds the data from SQL queries for the list
     * @var array<int,array>
     */
    private array $rows = [];

    /**
     * Nested eager-loaded data which applies to relations on records contained in this list
     * @var array<int,EagerLoadedList|DataObject>
     */
    private array $eagerLoadedData = [];

    private array $extraFields = [];

    private array $limitOffset = [null, 0];

    private string|array $sort = [];

    /**
     * Stored here so we can use it when constructing new lists based on this one
     */
    private array $manyManyComponent = [];

    /**
     * @param class-string<T> $dataClass
     */
    public function __construct(string $dataClass, string $dataListClass, int|array|null $foreignID = null, array $manyManyComponent = [])
    {
        if (!is_a($dataListClass, DataList::class, true)) {
            throw new LogicException('$dataListClass must be an instanceof DataList');
        }

        // relation lists require a valid foreignID or set of IDs
        if (is_a($dataListClass, RelationList::class, true) && !$this->isValidForeignID($foreignID)) {
            throw new InvalidArgumentException('$foreignID must be a valid ID for eager loaded relation lists');
        }

        $this->dataClass = $dataClass;
        $this->foreignID = $foreignID;
        $this->manyManyComponent = $manyManyComponent;

        // many_many relation lists have extra constructor args that don't apply for has_many or non-relations
        if (is_a($dataListClass, ManyManyThroughList::class, true)) {
            $this->dataList = ManyManyThroughList::create(
                $dataClass,
                // If someone instantiates one of these and passes DataObjectSchema::manyManyComponent() directly
                // the class will be in here as 'join'
                $manyManyComponent['joinClass'] ?? $manyManyComponent['join'],
                $manyManyComponent['childField'],
                $manyManyComponent['parentField'],
                $manyManyComponent['extraFields'],
                $dataClass,
                $manyManyComponent['parentClass']
            );
        } elseif (is_a($dataListClass, ManyManyList::class, true)) {
            $this->dataList = ManyManyList::create(
                $dataClass,
                $manyManyComponent['join'],
                $manyManyComponent['childField'],
                $manyManyComponent['parentField'],
                $manyManyComponent['extraFields']
            );
        } else {
            $this->dataList = $dataListClass::create($dataClass, '');
        }

        if (isset($manyManyComponent['extraFields'])) {
            $this->extraFields = $manyManyComponent['extraFields'];
        }
    }

    /**
     * Returns true if the variable passed in is valid for use in $this->dataList->forForeignID() on relation lists
     */
    private function isValidForeignID(int|array|null $foreignID): bool
    {
        // For an array, only return true if the array contains only integers and isn't empty
        if (is_array($foreignID)) {
            if (empty($foreignID)) {
                return false;
            }
            foreach ($foreignID as $id) {
                if (!is_int($id)) {
                    return false;
                }
            }
            return true;
        }
        // ID must be a valid ID int
        return $foreignID !== null && $foreignID >= 1;
    }

    /**
     * Pass in any eager-loaded data which applies to relations on a specific record in this list
     *
     * @return $this
     */
    public function addEagerLoadedData(string $relation, int $id, EagerLoadedList|DataObject $data): static
    {
        $this->eagerLoadedData[$id][$relation] = $data;
        return $this;
    }

    /**
     * Get the dataClass name for this list, ie the DataObject ClassName
     *
     * @return class-string<T>
     */
    public function dataClass(): string
    {
        return $this->dataClass;
    }

    public function dbObject($fieldName): ?DBField
    {
        return singleton($this->dataClass)->dbObject($fieldName);
    }

    public function getIDList(): array
    {
        $ids = $this->column('ID');
        return array_combine($ids, $ids);
    }

    /**
     * Sets the ComponentSet to be the given ID list
     * @throws BadMethodCallException
     */
    public function setByIDList($idList): void
    {
        throw new BadMethodCallException("Can't set the ComponentSet on an EagerLoadedList");
    }

    /**
     * Returns a copy of this list with the relationship linked to the given foreign ID
     * @throws BadMethodCallException
     */
    public function forForeignID($id): void
    {
        throw new BadMethodCallException("Can't change the foreign ID for an EagerLoadedList");
    }

    /**
     * @return Traversable<T>
     */
    public function getIterator(): Traversable
    {
        $limitedRows = $this->getFinalisedRows();
        foreach ($limitedRows as $row) {
            yield $this->createDataObject($row);
        }
    }

    /**
     * Get the raw data rows for the records in this list.
     * Doesn't include nested eagerloaded data.
     */
    public function getRows(): array
    {
        return array_values($this->rows);
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this as $item) {
            $result[] = $item;
        }
        return $result;
    }

    public function toNestedArray(): array
    {
        $result = [];
        foreach ($this as $item) {
            $result[] = $item->toMap();
        }
        return $result;
    }

    /**
     * Add a single row of database data.
     *
     * @throws InvalidArgumentException if there is no ID in $row
     */
    public function addRow(array $row): static
    {
        if (!array_key_exists('ID', $row) || $row['ID'] === null || $row['ID'] === '' || is_array($row['ID'])) {
            throw new InvalidArgumentException('$row must have a valid ID');
        }
        $this->rows[$row['ID']] = $row;
        return $this;
    }

    /**
     * Add multiple rows of database data.
     *
     * @throws InvalidArgumentException if any row is missing an ID
     */
    public function addRows(array $rows): static
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }
        return $this;
    }

    /**
     * Not implemented - use addRow instead.
     */
    public function add($item)
    {
        throw new BadMethodCallException('Cannot add a DataObject record to EagerLoadedList. Use addRow() to add database rows.');
    }

    /**
     * Removes a record from the list. Note that the record will not be removed from the
     * database - this list is read-only.
     */
    public function remove($item): static
    {
        $id = $item->ID;
        if (array_key_exists($id, $this->rows)) {
            unset($this->rows[$id]);
        }
        return $this;
    }

    public function first(): ?DataObject
    {
        $rows = $this->getFinalisedRows();
        if (count($rows) === 0) {
            return null;
        }
        return $this->byID(array_key_first($rows));
    }

    public function last(): ?DataObject
    {
        $rows = $this->getFinalisedRows();
        if (count($rows) === 0) {
            return null;
        }
        return $this->byID(array_key_last($rows));
    }

    public function map($keyField = 'ID', $titleField = 'Title'): Map
    {
        return new Map($this, $keyField, $titleField);
    }

    public function column($colName = 'ID'): array
    {
        $rows = $this->getFinalisedRows();

        if (count($rows) === 0) {
            return [];
        }

        if ($colName === 'id') {
            return array_keys($rows);
        }

        // Don't allow non-existent columns - see DataQuery::column()
        $id = array_key_first($rows);
        if (!array_key_exists($colName, $rows[$id])) {
            throw new InvalidArgumentException('Invalid column name ' . $colName);
        }

        return array_column($rows, $colName);
    }

    /**
     * Returns a unique array of a single field value for all the items in the list
     *
     * @param string $colName
     */
    public function columnUnique($colName = 'ID'): array
    {
        return array_unique($this->column($colName));
    }

    public function each($callback): static
    {
        foreach ($this as $row) {
            $callback($row);
        }
        return $this;
    }

    public function debug()
    {
        // Same implementation as DataList::debug()
        $val = '<h2>' . static::class . '</h2><ul>';
        foreach ($this->toNestedArray() as $item) {
            $val .= '<li style="list-style-type: disc; margin-left: 20px">' . Debug::text($item) . '</li>';
        }
        $val .= '</ul>';
        return $val;
    }

    /**
     * Returns whether an item with offset $key exists
     */
    public function offsetExists(mixed $key): bool
    {
        $count = $this->count();
        if (!is_int($key) || $count === 0 || $key >= $count) {
            return false;
        }

        if ($key < 0) {
            throw new InvalidArgumentException('$key can not be negative. -1 was provided.');
        }

        return true;
    }

    /**
     * Returns item stored in list with offset $key
     *
     * @return T|null
     */
    public function offsetGet(mixed $key): ?DataObject
    {
        if (!is_int($key)) {
            return null;
        }
        return $this->limit(1, $key)->first();
    }

    /**
     * Set an item with the key in $key
     * @throws BadMethodCallException
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        // Throw exception for compatability with DataList
        throw new BadMethodCallException("Can't alter items in an EagerLoadedList using array-access");
    }

    /**
     * Unset an item with the key in $key
     * @throws BadMethodCallException
     */
    public function offsetUnset(mixed $key): void
    {
        // Throw exception for compatability with DataList
        throw new BadMethodCallException("Can't alter items in an EagerLoadedList using array-access");
    }

    public function count(): int
    {
        return count($this->getFinalisedRows());
    }

    /**
     * Return the maximum value of the given field in this list
     *
     * @param string $fieldName
     */
    public function max($fieldName): mixed
    {
        return max($this->column($fieldName));
    }

    /**
     * Return the minimum value of the given field in this list
     *
     * @param string $fieldName
     */
    public function min($fieldName): mixed
    {
        return min($this->column($fieldName));
    }

    /**
     * Return the average value of the given field in this list
     *
     * @param string $fieldName
     */
    public function avg($fieldName): mixed
    {
        // We have to rely on the database to either give us the right answer or throw the right exception.
        // MySQL does wiether things with sum, e.g. an average for the Text field "1_1" is 1 - but
        // other database implementations could behave differently.
        $list = $this->foreignID ? $this->dataList->forForeignID($this->foreignID) : $this->dataList;
        return $list->byIDs($this->column('ID'))->avg($fieldName);
    }

    /**
     * Return the sum of the values of the given field in this list
     *
     * @param string $fieldName
     */
    public function sum($fieldName): int|float
    {
        // We have to rely on the database to either give us the right answer or throw the right exception.
        // MySQL does wiether things with sum, e.g. a sum for the Text field "1_1" is 1 - but
        // other database implementations could behave differently.
        $list = $this->foreignID ? $this->dataList->forForeignID($this->foreignID) : $this->dataList;
        return $list->byIDs($this->column('ID'))->sum($fieldName);
    }

    /**
     * Returns true if this list has items
     */
    public function exists(): bool
    {
        return $this->count() !== 0;
    }

    public function canFilterBy($fieldName): bool
    {
        if (!is_string($fieldName) || empty($this->rows)) {
            return false;
        }

        $id = array_key_first($this->rows);
        return array_key_exists($fieldName, $this->rows[$id]);
    }

    public function canSortBy($fieldName): bool
    {
        return $this->canFilterBy($fieldName);
    }

    public function find($key, $value): ?DataObject
    {
        return $this->filter($key, $value)->first();
    }

    public function filter(...$args): static
    {
        $filters = $this->normaliseFilterArgs($args, __FUNCTION__);
        $list = clone $this;
        $list->rows = $this->getMatches($filters);
        return $list;
    }

    public function filterAny(...$args): static
    {
        $filters = $this->normaliseFilterArgs($args, __FUNCTION__);
        $list = clone $this;
        $list->rows = $this->getMatches($filters, true);
        return $list;
    }

    public function exclude(...$args): static
    {
        $filters = $this->normaliseFilterArgs($args, __FUNCTION__);
        $toRemove = $this->getMatches($filters);
        $list = clone $this;
        foreach ($toRemove as $id => $row) {
            unset($list->rows[$id]);
        }
        return $list;
    }

    /**
     * Return a copy of this list which does not contain any items with any of these params
     *
     * @return static<T>
     */
    public function excludeAny(...$args): static
    {
        $filters = $this->normaliseFilterArgs($args, __FUNCTION__);
        $toRemove = $this->getMatches($filters, true);
        $list = clone $this;
        foreach ($toRemove as $id => $row) {
            unset($list->rows[$id]);
        }
        return $list;
    }

    /**
     * Return a new instance of the list with an added filter
     *
     * @param array $filterArray
     * @return static<T>
     */
    public function addFilter($filterArray): static
    {
        $list = clone $this;
        $list->rows = $this->getMatches($filterArray);
        return $list;
    }

    /**
     * This method returns a copy of this list that does not contain any DataObjects that exists in $list
     *
     * The $list passed needs to contain the same dataclass as $this
     *
     * @return static<T>
     * @throws InvalidArgumentException
     */
    public function subtract(DataList $list): static
    {
        if ($this->dataClass() != $list->dataClass()) {
            throw new InvalidArgumentException('The list passed must have the same dataclass as this class');
        }
        return $this->exclude('ID', $list->column('ID'));
    }

    /**
     * Validate and process arguments - see DataList::filter(), DataList::exclude(), etc.
     */
    private function normaliseFilterArgs(array $arguments, string $function): array
    {
        switch (count($arguments)) {
            case 1:
                $filter = $arguments[0];
                break;
            case 2:
                $filter = [$arguments[0] => $arguments[1]];
                break;
            default:
                throw new InvalidArgumentException("Incorrect number of arguments passed to $function");
        }
        foreach (array_keys($filter) as $column) {
            if (!$this->canFilterBy(explode(':', $column)[0])) {
                throw new InvalidArgumentException("Can't filter by column '$column'");
            }
        }

        return $filter;
    }

    /**
     * Get all rows which match the given filters.
     * If $any is false, all filters in the $filters array must match.
     * If $any is true, ANY filter in the $filters array must match.
     */
    private function getMatches($filters, bool $any = false): array
    {
        $matches = [];
        $searchFilters = [];

        foreach ($filters as $filterKey => $filterValue) {
            $searchFilters[$filterKey] = $this->createSearchFilter($filterKey, $filterValue);
        }

        foreach ($this->rows as $id => $row) {
            $doesMatch = true;
            foreach ($filters as $column => $value) {
                // Throw exception for empty $value arrays to match ExactMatchFilter::manyFilter
                if (is_array($value) && empty($value)) {
                    throw new InvalidArgumentException("Cannot filter $column against an empty set");
                }
                $searchFilter = $searchFilters[$column];
                $extractedValue = $this->extractValue($row, $this->standardiseColumn($searchFilter->getFullName()));
                $doesMatch = $searchFilter->matches($extractedValue);
                if (!$any && !$doesMatch) {
                    $doesMatch = false;
                    break;
                }
                if ($any && $doesMatch) {
                    break;
                }
            }
            if ($doesMatch) {
                $matches[$id] = $row;
            }
        }
        return $matches;
    }

    /**
     * Extracts a value from an item in the list, where the item is either an
     * object or array.
     *
     * @param string $key They key for the value to be extracted. Implied mixed type
     * for compatability with DataList.
     */
    private function extractValue(array $row, $key): mixed
    {
        if (array_key_exists($key, $row)) {
            return $row[$key];
        }

        return null;
    }

    /**
     * @return ArrayList<T>
     */
    public function filterByCallback($callback): ArrayList
    {
        if (!is_callable($callback)) {
            throw new LogicException(sprintf(
                "SS_Filterable::filterByCallback() passed callback must be callable, '%s' given",
                gettype($callback)
            ));
        }

        $output = ArrayList::create();
        foreach ($this as $item) {
            if (call_user_func($callback, $item, $this)) {
                $output->push($item);
            }
        }
        return $output;
    }

    public function byID($id): ?DataObject
    {
        $rows = $this->getFinalisedRows();
        if (!array_key_exists($id, $rows)) {
            return null;
        }
        return $this->createDataObject($rows[$id]);
    }

    public function byIDs($ids): static
    {
        $list = clone $this;
        $ids = array_map('intval', (array) $ids);
        $list->rows = ArrayLib::filter_keys($list->rows, $ids);
        return $list;
    }

    public function sort(...$args): static
    {
        $count = count($args);
        if ($count == 0) {
            return $this;
        }
        if ($count > 2) {
            throw new InvalidArgumentException('This method takes zero, one or two arguments');
        }

        if ($count == 2) {
            list($column, $direction) = $args;
            $sort = [$this->standardiseColumn($column) => $direction];
        } else {
            $sort = $args[0];
            if (!is_string($sort) && !is_array($sort) && !is_null($sort)) {
                throw new InvalidArgumentException('sort() arguments must either be a string, an array, or null');
            }
            if (is_null($sort)) {
                // Setting sort to null means we just use the default sort order.
                $list = clone $this;
                $list->sort = [];
                return $list;
            } elseif (empty($sort)) {
                throw new InvalidArgumentException('Invalid sort parameter');
            }
            // If $sort is string then convert string to array to allow for validation
            if (is_string($sort)) {
                $newSort = [];
                // Making the assumption here there are no commas in column names
                // Other parts of silverstripe will break if there are commas in column names
                foreach (explode(',', $sort) as $colDir) {
                    // Using regex instead of explode(' ') in case column name includes spaces
                    if (preg_match('/^(.+) ([^"]+)$/i', trim($colDir), $matches)) {
                        list($column, $direction) = [$matches[1], $matches[2]];
                    } else {
                        list($column, $direction) = [$colDir, 'ASC'];
                    }
                    $newSort[$this->standardiseColumn($column)] = $direction;
                }
                $sort = $newSort;
            }
        }

        foreach ($sort as $column => $direction) {
            // validate and normalise sort column
            $this->validateSortColumn($column);

            // validate sort direction
            if (!in_array(strtolower($direction), ['asc', 'desc'])) {
                throw new InvalidArgumentException("Invalid sort direction $direction");
            }
        }

        $list = clone $this;
        $list->sort = $sort;
        return $list;
    }

    /**
     * Shuffle the items in this list
     *
     * @return static<T>
     */
    public function shuffle(): static
    {
        $list = clone $this;
        $list->sort = 'shuffle';
        return $list;
    }

    private function standardiseColumn(string $column): string
    {
        // Strip whitespace and double quotes from single field names e.g. '"Title"'
        $column = trim($column);
        if (preg_match('#^"[^"]+"$#', $column)) {
            $column = str_replace('"', '', $column);
        }
        return $column;
    }

    private function validateSortColumn(string $column): void
    {
        $columnName = $column;

        if (preg_match('/^[A-Z0-9\._]+$/i', $column ?? '')) {
            $relations = explode('.', $column ?? '');
            $fieldName = array_pop($relations);

            $relationModelClass = $this->dataClass();

            foreach ($relations as $relation) {
                $prevModelClass = $relationModelClass;
                /** @var DataObject $singleton */
                $singleton = singleton($relationModelClass);
                $relationModelClass = $singleton->getRelationClass($relation);
                // See DataQuery::applyRelation() which is called indirectly from DataList::validateSortColumn()
                // for context on these exceptions.
                if ($relationModelClass === null) {
                    throw new InvalidArgumentException("$relation is not a relation on model $prevModelClass");
                }
                if (in_array($singleton->getRelationType($relation), ['has_many', 'many_many', 'belongs_many_many'])) {
                    throw new InvalidArgumentException("$relation is not a linear relation on model $prevModelClass");
                }
            }

            if (strpos($column, '.') === false) {
                if (!singleton($relationModelClass)->hasDatabaseField($column)) {
                    throw new DatabaseException("Unknown column \"$column\"");
                }
                $columnName = '"' . $column . '"';
            } else {
                // Find the db field the relation belongs to - It will be returned in quoted SQL "TableName"."ColumnName" notation
                // Note that sqlColumnForField() throws an expected exception if the field doesn't exist on the relation
                $relationPrefix = DataQuery::applyRelationPrefix($relations);
                $columnName = DataObject::getSchema()->sqlColumnForField($relationModelClass, $fieldName, $relationPrefix);
            }

            // All of the above is necessary to ensure the expected exceptions are thrown for invalid relations
            // But we still need to ultimately throw an exception here, because sorting by relations isn't
            // currently supported at all for this class.
            if (!empty($relations)) {
                throw new InvalidArgumentException('Cannot sort by relations on EagerLoadedList');
            }
        }

        // If $columnName is equal to $col it means that it was orginally raw sql or otherwise invalid.
        if ($columnName === $column) {
            throw new InvalidArgumentException("Invalid sort column $column");
        }
    }

    public function reverse(): static
    {
        // No-op if we're gonna shuffle the list anyway
        if ($this->sort === 'shuffle') {
            return $this;
        }
        // Set the sort order for each clause to be reversed
        // This is how DataList reverses its list order as well
        $list = clone $this;
        foreach ($list->sort as $clause => &$dir) {
            $dir = (strtoupper($dir) == 'DESC') ? 'ASC' : 'DESC';
        }
        return $list;
    }

    public function limit(?int $length, int $offset = 0): static
    {
        if ($length !== null && $length < 0) {
            throw new InvalidArgumentException("\$length can not be negative. $length was provided.");
        }

        if ($offset < 0) {
            throw new InvalidArgumentException("\$offset can not be negative. $offset was provided.");
        }

        // We don't actually apply the limit immediately, for compatability with the way it works in DataList
        $list = clone $this;
        $list->limitOffset = [$length, $offset];
        return $list;
    }

    /**
     * Check if this list has an item with the given ID
     */
    public function hasID(int $id): bool
    {
        return array_key_exists($id, $this->getFinalisedRows());
    }

    public function relation($relationName): ?Relation
    {
        $ids = $this->column('ID');

        $prototypicalList = null;

        // If we've already got that data loaded, don't trigger a new DB query
        $relations = [];
        foreach ($ids as $id) {
            if (!isset($this->eagerLoadedData[$id][$relationName])) {
                continue;
            }
            $data = $this->eagerLoadedData[$id][$relationName];
            if (!($data instanceof EagerLoadedList)) {
                // There's no clean way to get the rows back out of DataObject records,
                // and if it's not a DataObject then we don't know how to handle it,
                // so fall back to a new DB query
                break;
            }
            $prototypicalList = $data;
            $relations = array_merge($relations, $data->getRows());
        }

        if (!empty($relations)) {
            $relation = EagerLoadedList::create(
                $prototypicalList->dataClass(),
                get_class($prototypicalList->dataList),
                $ids,
                $prototypicalList->manyManyComponent
            );
            $relation->addRows($relations);
            return $relation;
        }

        // Trigger a new DB query if needed - see DataList::relation()
        $singleton = DataObject::singleton($this->dataClass);
        $relation = $singleton->$relationName($ids);

        return $relation;
    }

    /**
     * Create a DataObject from the given SQL row.
     * At a minimum, $row['ID'] must be set. Unsaved records cannot be eager loaded.
     *
     * @param array $row
     * @return T
     */
    public function createDataObject($row): DataObject
    {
        if (!array_key_exists('ID', $row)) {
            throw new InvalidArgumentException('$row must have an ID');
        }
        $record = $this->dataList->createDataObject($row);
        $this->setDataObjectEagerLoadedData($row['ID'], $record);
        return $record;
    }

    /**
     * Find the extra field data for a single row of the relationship join
     * table for many_many relations, given the known child ID.
     *
     * @param string $componentName The name of the component (unused, but kept for compatability with ManyManyList)
     * @param int|string $itemID The ID of the child for the relationship
     *
     * @return array Map of fieldName => fieldValue
     * @throws BadMethodCallException if the relation type for this list is not many_many
     * @throws InvalidArgumentException if $itemID is not numeric
     */
    public function getExtraData($componentName, int|string $itemID): array
    {
        if (!($this->dataList instanceof ManyManyList) && !($this->dataList instanceof ManyManyThroughList)) {
            throw new BadMethodCallException('Cannot have extra fields on this list type');
        }

        // Allow string IDs for compatability with ManyManyList
        if (!is_numeric($itemID)) {
            throw new InvalidArgumentException('$itemID must be an integer or numeric string');
        }

        $itemID = (int)$itemID;
        $rows = $this->getFinalisedRows();

        // Skip if no extrafields or record not in this list
        if (empty($this->extraFields) || !array_key_exists($itemID, $rows)) {
            return [];
        }

        $result = [];
        foreach ($this->extraFields as $fieldName => $spec) {
            $row = $rows[$itemID];
            if (array_key_exists($fieldName, $row)) {
                $result[$fieldName] = $row[$fieldName];
            } else {
                $result[$fieldName] = null;
            }
        }
        return $result;
    }

    /**
     * Gets the extra fields included in the relationship.
     *
     * @return array a map of field names to types
     * @throws BadMethodCallException if the relation type for this list is not many_many
     */
    public function getExtraFields(): array
    {
        if (!($this->dataList instanceof ManyManyList) && !($this->dataList instanceof ManyManyThroughList)) {
            throw new BadMethodCallException('Cannot have extra fields on this list type');
        }
        return $this->extraFields;
    }

    private function setDataObjectEagerLoadedData(int $id, DataObject $item): void
    {
        if (array_key_exists($id, $this->eagerLoadedData)) {
            foreach ($this->eagerLoadedData[$id] as $relation => $data) {
                $item->setEagerLoadedData($relation, $data);
            }
        }
    }

    /**
     * Gets the final rows for this list after applying all transformations.
     * Currently only limit and sort are applied lazily, but filter could be done this was as well in the future.
     */
    private function getFinalisedRows(): array
    {
        return $this->doLimit($this->doSort($this->rows));
    }

    private function doLimit(array $rows): array
    {
        list($length, $offset) = $this->limitOffset;

        // If the limit is 0, return an empty list.
        if ($length === 0) {
            return [];
        }

        return array_slice($rows, $offset, $length, true);
    }

    private function doSort(array $rows): array
    {
        // Do nothing if there's no defined sort order.
        if (empty($this->sort)) {
            return $rows;
        }

        if ($this->sort === 'shuffle') {
            ArrayLib::shuffleAssociative($rows);
            return $rows;
        }

        uasort($rows, function (array $row, array $other): int {
            $compared = 0;
            foreach ($this->sort as $column => $direction) {
                $rowValue = $this->extractValue($row, $column);
                $otherValue = $this->extractValue($other, $column);
                // We need to treat numbers differently than numeric strings to match database behaviour
                if ($this->isNumericNotString($rowValue) && $this->isNumericNotString($otherValue)) {
                    $compared = $rowValue <=> $otherValue;
                } else {
                    $compared = strcasecmp($rowValue ?? '', $otherValue ?? '');
                }
                if ($compared !== 0) {
                    // Reverse the direction for desc; i.e. -1 becomes 1 and 1 becomes -1
                    if (strtolower($direction) === 'desc') {
                        $compared *= -1;
                    }
                    // If the comparison clearly marks an order, we don't need to check the remaining columns.
                    break;
                }
            }
            return $compared;
        });

        return $rows;
    }

    private function isNumericNotString(mixed $value): bool
    {
        return is_numeric($value) && !is_string($value);
    }
}
