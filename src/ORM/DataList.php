<?php

namespace SilverStripe\ORM;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\Filters\SearchFilter;
use SilverStripe\ORM\Queries\SQLConditionGroup;
use SilverStripe\View\ViewableData;
use Exception;
use InvalidArgumentException;
use LogicException;
use BadMethodCallException;
use SilverStripe\ORM\Connect\Query;
use Traversable;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\ArrayList;

/**
 * Implements a "lazy loading" DataObjectSet.
 * Uses {@link DataQuery} to do the actual query generation.
 *
 * DataLists are _immutable_ as far as the query they represent is concerned. When you call a method that
 * alters the query, a new DataList instance is returned, rather than modifying the existing instance
 *
 * When you add or remove an element to the list the query remains the same, but because you have modified
 * the underlying data the contents of the list changes. These are some of those methods:
 *
 *   - add
 *   - addMany
 *   - remove
 *   - removeMany
 *   - removeByID
 *   - removeByFilter
 *   - removeAll
 *
 * Subclasses of DataList may add other methods that have the same effect.
 */
class DataList extends ViewableData implements SS_List, Filterable, Sortable, Limitable
{

    /**
     * The DataObject class name that this data list is querying
     *
     * @var string
     */
    protected $dataClass;

    /**
     * The {@link DataQuery} object responsible for getting this DataList's records
     *
     * @var DataQuery
     */
    protected $dataQuery;

    /**
     * A cached Query to save repeated database calls. {@see DataList::getTemplateIteratorCount()}
     *
     * @var SilverStripe\ORM\Connect\Query
     */
    protected $finalisedQuery;


    private array $eagerLoadRelations = [];

    private array $eagerLoadedData = [];

    /**
     * Create a new DataList.
     * No querying is done on construction, but the initial query schema is set up.
     *
     * @param string $dataClass - The DataObject class to query.
     */
    public function __construct($dataClass)
    {
        $this->dataClass = $dataClass;
        $this->dataQuery = new DataQuery($this->dataClass);

        parent::__construct();
    }

    /**
     * Get the dataClass name for this DataList, ie the DataObject ClassName
     *
     * @return string
     */
    public function dataClass()
    {
        return $this->dataClass;
    }

    /**
     * When cloning this object, clone the dataQuery object as well
     */
    public function __clone()
    {
        $this->dataQuery = clone $this->dataQuery;
        $this->finalisedQuery = null;
        $this->eagerLoadedData = [];
    }

    /**
     * Return a copy of the internal {@link DataQuery} object
     *
     * Because the returned value is a copy, modifying it won't affect this list's contents. If
     * you want to alter the data query directly, use the alterDataQuery method
     *
     * @return DataQuery
     */
    public function dataQuery()
    {
        return clone $this->dataQuery;
    }

    /**
     * @var bool - Indicates if we are in an alterDataQueryCall already, so alterDataQuery can be re-entrant
     */
    protected $inAlterDataQueryCall = false;

    /**
     * Return a new DataList instance with the underlying {@link DataQuery} object altered
     *
     * If you want to alter the underlying dataQuery for this list, this wrapper method
     * will ensure that you can do so without mutating the existing List object.
     *
     * It clones this list, calls the passed callback function with the dataQuery of the new
     * list as it's first parameter (and the list as it's second), then returns the list
     *
     * Note that this function is re-entrant - it's safe to call this inside a callback passed to
     * alterDataQuery
     *
     * @param callable $callback
     * @return static
     * @throws Exception
     */
    public function alterDataQuery($callback)
    {
        if ($this->inAlterDataQueryCall) {
            $list = $this;

            $res = call_user_func($callback, $list->dataQuery, $list);
            if ($res) {
                $list->dataQuery = $res;
            }

            return $list;
        }

        $list = clone $this;
        $list->inAlterDataQueryCall = true;

        try {
            $res = $callback($list->dataQuery, $list);
            if ($res) {
                $list->dataQuery = $res;
            }
        } catch (Exception $e) {
            $list->inAlterDataQueryCall = false;
            throw $e;
        }

        $list->inAlterDataQueryCall = false;
        return $list;
    }

    /**
     * Return a new DataList instance with the underlying {@link DataQuery} object changed
     *
     * @param DataQuery $dataQuery
     * @return static
     */
    public function setDataQuery(DataQuery $dataQuery)
    {
        $clone = clone $this;
        $clone->dataQuery = $dataQuery;
        return $clone;
    }

    /**
     * Returns a new DataList instance with the specified query parameter assigned
     *
     * @param string|array $keyOrArray Either the single key to set, or an array of key value pairs to set
     * @param mixed $val If $keyOrArray is not an array, this is the value to set
     * @return static
     */
    public function setDataQueryParam($keyOrArray, $val = null)
    {
        $clone = clone $this;

        if (is_array($keyOrArray)) {
            foreach ($keyOrArray as $key => $value) {
                $clone->dataQuery->setQueryParam($key, $value);
            }
        } else {
            $clone->dataQuery->setQueryParam($keyOrArray, $val);
        }

        return $clone;
    }

    /**
     * Returns the SQL query that will be used to get this DataList's records.  Good for debugging. :-)
     *
     * @param array $parameters Out variable for parameters required for this query
     * @return string The resulting SQL query (may be parameterised)
     */
    public function sql(&$parameters = [])
    {
        return $this->dataQuery->query()->sql($parameters);
    }

    /**
     * Return a new DataList instance with a WHERE clause added to this list's query.
     *
     * This method accepts raw SQL so could be vulnerable to SQL injection attacks if used incorrectly,
     * it's preferable to use filter() instead which does not allow raw SQL.
     *
     * Supports parameterised queries.
     * See SQLSelect::addWhere() for syntax examples, although DataList
     * won't expand multiple method arguments as SQLSelect does.
     *
     *
     * @param string|array|SQLConditionGroup $filter Predicate(s) to set, as escaped SQL statements or
     * paramaterised queries
     * @return static
     */
    public function where($filter)
    {
        return $this->alterDataQuery(function (DataQuery $query) use ($filter) {
            $query->where($filter);
        });
    }

    /**
     * Return a new DataList instance with a WHERE clause added to this list's query.
     * All conditions provided in the filter will be joined with an OR
     *
     * This method accepts raw SQL so could be vulnerable to SQL injection attacks if used incorrectly,
     * it's preferable to use filterAny() instead which does not allow raw SQL
     *
     * Supports parameterised queries.
     * See SQLSelect::addWhere() for syntax examples, although DataList
     * won't expand multiple method arguments as SQLSelect does.
     *
     * @param string|array|SQLConditionGroup $filter Predicate(s) to set, as escaped SQL statements or
     * paramaterised queries
     * @return static
     */
    public function whereAny($filter)
    {
        return $this->alterDataQuery(function (DataQuery $query) use ($filter) {
            $query->whereAny($filter);
        });
    }

    /**
     * Returns true if this DataList can be sorted by the given field.
     *
     * @param string $fieldName
     * @return boolean
     */
    public function canSortBy($fieldName)
    {
        return $this->dataQuery()->query()->canSortBy($fieldName);
    }

    /**
     * Returns true if this DataList can be filtered by the given field.
     *
     * @param string $fieldName (May be a related field in dot notation like Member.FirstName)
     * @return boolean
     */
    public function canFilterBy($fieldName)
    {
        $model = singleton($this->dataClass);
        $relations = explode(".", $fieldName ?? '');
        // First validate the relationships
        $fieldName = array_pop($relations);
        foreach ($relations as $r) {
            $relationClass = $model->getRelationClass($r);
            if (!$relationClass) {
                return false;
            }
            $model = singleton($relationClass);
            if (!$model) {
                return false;
            }
        }
        // Then check field
        if ($model->hasDatabaseField($fieldName)) {
            return true;
        }
        return false;
    }

    /**
     * Return a new DataList instance with the records returned in this query
     * restricted by a limit clause.
     */
    public function limit(?int $length, int $offset = 0): static
    {
        if ($length !== null && $length < 0) {
            throw new InvalidArgumentException("\$length can not be negative. $length was provided.");
        }

        if ($offset < 0) {
            throw new InvalidArgumentException("\$offset can not be negative. $offset was provided.");
        }

        return $this->alterDataQuery(function (DataQuery $query) use ($length, $offset) {
            $query->limit($length, $offset);
        });
    }

    /**
     * Return a new DataList instance with distinct records or not
     *
     * @param bool $value
     * @return static
     */
    public function distinct($value)
    {
        return $this->alterDataQuery(function (DataQuery $query) use ($value) {
            $query->distinct($value);
        });
    }

    /**
     * Return a new DataList instance as a copy of this data list with the sort order set
     *
     * Raw SQL is not accepted, only actual field names can be passed
     *
     * @param string|array $args
     * @example $list = $list->sort('Name'); // default ASC sorting
     * @example $list = $list->sort('"Name"'); // field names can have double quotes around them
     * @example $list = $list->sort('Name ASC, Age DESC');
     * @example $list = $list->sort('Name', 'ASC');
     * @example $list = $list->sort(['Name' => 'ASC', 'Age' => 'DESC']);
     * @example $list = $list->sort('MyRelation.MyColumn ASC')
     * @example $list->sort(null); // wipe any existing sort
     */
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
            $sort = [$column => $direction];
        } else {
            $sort = $args[0];
            if (!is_string($sort) && !is_array($sort) && !is_null($sort)) {
                throw new InvalidArgumentException('sort() arguments must either be a string, an array, or null');
            }
            if (is_null($sort)) {
                // Set an an empty array here to cause any existing sort on the DataLists to be wiped
                // later on in this method
                $sort = [];
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
                    $newSort[$column] = $direction;
                }
                $sort = $newSort;
            }
        }
        foreach ($sort as $column => $direction) {
            $this->validateSortColumn($column);
            $this->validateSortDirection($direction);
        }
        return $this->alterDataQuery(function (DataQuery $query, DataList $list) use ($sort) {
            // Wipe the sort
            $query->sort(null, null);
            foreach ($sort as $column => $direction) {
                $list->applyRelation($column, $relationColumn, true);
                $query->sort($relationColumn, $direction, false);
            }
        });
    }

    private function validateSortColumn(string $column): void
    {
        $col = trim($column);
        // Strip double quotes from single field names e.g. '"Title"'
        if (preg_match('#^"[^"]+"$#', $col)) {
            $col = str_replace('"', '', $col);
        }
        // $columnName is a param that is passed by reference so is essentially as a return type
        // it will be returned in quoted SQL "TableName"."ColumnName" notation
        // if it's equal to $col however it means that it WAS orginally raw sql, which is disallowed for sort()
        //
        // applyRelation() will also throw an InvalidArgumentException if $column is not raw sql but
        // the Relation.FieldName is not a valid model relationship
        $this->applyRelation($col, $columnName, true);
        if ($col === $columnName) {
            throw new InvalidArgumentException("Invalid sort column $column");
        }
    }

    private function validateSortDirection(string $direction): void
    {
        $dir = strtolower($direction);
        if ($dir !== 'asc' && $dir !== 'desc') {
            throw new InvalidArgumentException("Invalid sort direction $direction");
        }
    }

    /**
     * Set an explicit ORDER BY statement using raw SQL
     *
     * This method accepts raw SQL so could be vulnerable to SQL injection attacks if used incorrectly,
     * it's preferable to use sort() instead which does not allow raw SQL
     */
    public function orderBy(string $orderBy): static
    {
        return $this->alterDataQuery(function (DataQuery $query) use ($orderBy) {
            $query->sort($orderBy, null, true);
        });
    }

    /**
     * Return a copy of this list which only includes items with these characteristics
     *
     * Raw SQL is not accepted, only actual field names can be passed
     *
     * @see Filterable::filter()
     *
     * @example $list = $list->filter('Name', 'bob'); // only bob in the list
     * @example $list = $list->filter('Name', array('aziz', 'bob'); // aziz and bob in list
     * @example $list = $list->filter(array('Name'=>'bob', 'Age'=>21)); // bob with the age 21
     * @example $list = $list->filter(array('Name'=>'bob', 'Age'=>array(21, 43))); // bob with the Age 21 or 43
     * @example $list = $list->filter(array('Name'=>array('aziz','bob'), 'Age'=>array(21, 43)));
     *          // aziz with the age 21 or 43 and bob with the Age 21 or 43
     *
     * Note: When filtering on nullable columns, null checks will be automatically added.
     * E.g. ->filter('Field:not', 'value) will generate '... OR "Field" IS NULL', and
     * ->filter('Field:not', null) will generate '"Field" IS NOT NULL'
     *
     * @todo extract the sql from $customQuery into a SQLGenerator class
     *
     * @param string|array Escaped SQL statement. If passed as array, all keys and values will be escaped internally
     * @return $this
     */
    public function filter()
    {
        // Validate and process arguments
        $arguments = func_get_args();
        switch (sizeof($arguments ?? [])) {
            case 1:
                $filters = $arguments[0];

                break;
            case 2:
                $filters = [$arguments[0] => $arguments[1]];

                break;
            default:
                throw new InvalidArgumentException('Incorrect number of arguments passed to filter()');
        }

        return $this->addFilter($filters);
    }

    /**
     * Return a new instance of the list with an added filter
     *
     * @param array $filterArray
     * @return $this
     */
    public function addFilter($filterArray)
    {
        $list = $this;

        foreach ($filterArray as $expression => $value) {
            $filter = $this->createSearchFilter($expression, $value);
            $list = $list->alterDataQuery([$filter, 'apply']);
        }

        return $list;
    }

    /**
     * Return a copy of this list which contains items matching any of these characteristics.
     *
     * Raw SQL is not accepted, only actual field names can be passed
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
     * @todo extract the sql from this method into a SQLGenerator class
     *
     * @param string|array See {@link filter()}
     * @return static
     */
    public function filterAny()
    {
        $numberFuncArgs = count(func_get_args());
        $whereArguments = [];

        if ($numberFuncArgs == 1 && is_array(func_get_arg(0))) {
            $whereArguments = func_get_arg(0);
        } elseif ($numberFuncArgs == 2) {
            $whereArguments[func_get_arg(0)] = func_get_arg(1);
        } else {
            throw new InvalidArgumentException('Incorrect number of arguments passed to filterAny()');
        }

        $list = $this->alterDataQuery(function (DataQuery $query) use ($whereArguments) {
            $subquery = $this->getFilterAnySubquery($query, $whereArguments);
            foreach ($whereArguments as $field => $value) {
                $filter = $this->createSearchFilter($field, $value);
                $filter->apply($subquery);
            }
        });

        return $list;
    }

    private function getFilterAnySubquery(DataQuery $query, array $whereArguments): DataQuery_SubGroup
    {
        $clause = 'WHERE';
        foreach (array_keys($whereArguments) as $field) {
            if (preg_match('#\.(COUNT|SUM|AVG|MIN|MAX)\(#', strtoupper($field))) {
                $clause = 'HAVING';
                break;
            }
        }
        return $query->disjunctiveGroup($clause);
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
        /** @var ArrayList $output */
        $output = ArrayList::create();
        foreach ($this as $item) {
            if (call_user_func($callback, $item, $this)) {
                $output->push($item);
            }
        }
        return $output;
    }

    /**
     * Given a field or relation name, apply it safely to this datalist.
     *
     * Unlike getRelationName, this is immutable and will fallback to the quoted field
     * name if not a relation.
     *
     * Example use (simple WHERE condition on data sitting in a related table):
     *
     * <code>
     *  $columnName = null;
     *  $list = Page::get()
     *    ->applyRelation('TaxonomyTerms.ID', $columnName)
     *    ->where([$columnName => 'my value']);
     * </code>
     *
     *
     * @param string $field Name of field or relation to apply
     * @param string $columnName Quoted column name (by reference)
     * @param bool $linearOnly Set to true to restrict to linear relations only. Set this
     * if this relation will be used for sorting, and should not include duplicate rows.
     * @return $this DataList with this relation applied
     */
    public function applyRelation($field, &$columnName = null, $linearOnly = false)
    {
        // If field is invalid, return it without modification
        if (!$this->isValidRelationName($field)) {
            $columnName = $field;
            return $this;
        }

        // Simple fields without relations are mapped directly
        if (strpos($field ?? '', '.') === false) {
            $columnName = '"' . $field . '"';
            return $this;
        }

        return $this->alterDataQuery(
            function (DataQuery $query) use ($field, &$columnName, $linearOnly) {
                $relations = explode('.', $field ?? '');
                $fieldName = array_pop($relations);

                // Apply relation
                $relationModelName = $query->applyRelation($relations, $linearOnly);
                $relationPrefix = $query->applyRelationPrefix($relations);

                // Find the db field the relation belongs to
                $columnName = DataObject::getSchema()
                    ->sqlColumnForField($relationModelName, $fieldName, $relationPrefix);
            }
        );
    }

    /**
     * Check if the given field specification could be interpreted as an unquoted relation name
     *
     * @param string $field
     * @return bool
     */
    protected function isValidRelationName($field)
    {
        return preg_match('/^[A-Z0-9\._]+$/i', $field ?? '');
    }

    /**
     * Given a filter expression and value construct a {@see SearchFilter} instance
     *
     * @param string $filter E.g. `Name:ExactMatch:not`, `Name:ExactMatch`, `Name:not`, `Name`
     * @param mixed $value Value of the filter
     * @return SearchFilter
     */
    protected function createSearchFilter($filter, $value)
    {
        // Field name is always the first component
        $fieldArgs = explode(':', $filter ?? '');
        $fieldName = array_shift($fieldArgs);

        // Inspect type of second argument to determine context
        $secondArg = array_shift($fieldArgs);
        $modifiers = $fieldArgs;
        if (!$secondArg) {
            // Use default filter if none specified. E.g. `->filter(['Name' => $myname])`
            $filterServiceName = 'DataListFilter.default';
        } else {
            // The presence of a second argument is by default ambiguous; We need to query
            // Whether this is a valid modifier on the default filter, or a filter itself.
            /** @var SearchFilter $defaultFilterInstance */
            $defaultFilterInstance = Injector::inst()->get('DataListFilter.default');
            if (in_array(strtolower($secondArg ?? ''), $defaultFilterInstance->getSupportedModifiers() ?? [])) {
                // Treat second (and any subsequent) argument as modifiers, using default filter
                $filterServiceName = 'DataListFilter.default';
                array_unshift($modifiers, $secondArg);
            } else {
                // Second argument isn't a valid modifier, so assume is filter identifier
                $filterServiceName = "DataListFilter.{$secondArg}";
            }
        }

        // Build instance
        return Injector::inst()->create($filterServiceName, $fieldName, $value, $modifiers);
    }

    /**
     * Return a copy of this list which does not contain any items that match all params
     *
     * Raw SQL is not accepted, only actual field names can be passed
     *
     * @example $list = $list->exclude('Name', 'bob'); // exclude bob from list
     * @example $list = $list->exclude('Name', array('aziz', 'bob'); // exclude aziz and bob from list
     * @example $list = $list->exclude(array('Name'=>'bob, 'Age'=>21)); // exclude bob that has Age 21
     * @example $list = $list->exclude(array('Name'=>'bob, 'Age'=>array(21, 43))); // exclude bob with Age 21 or 43
     * @example $list = $list->exclude(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43)));
     *          // bob age 21 or 43, phil age 21 or 43 would be excluded
     *
     * @todo extract the sql from this method into a SQLGenerator class
     *
     * @param string|array
     * @param string [optional]
     *
     * @return $this
     */
    public function exclude()
    {
        $numberFuncArgs = count(func_get_args());
        $whereArguments = [];

        if ($numberFuncArgs == 1 && is_array(func_get_arg(0))) {
            $whereArguments = func_get_arg(0);
        } elseif ($numberFuncArgs == 2) {
            $whereArguments[func_get_arg(0)] = func_get_arg(1);
        } else {
            throw new InvalidArgumentException('Incorrect number of arguments passed to exclude()');
        }

        return $this->alterDataQuery(function (DataQuery $query) use ($whereArguments) {
            $subquery = $query->disjunctiveGroup();

            foreach ($whereArguments as $field => $value) {
                $filter = $this->createSearchFilter($field, $value);
                $filter->exclude($subquery);
            }
        });
    }

    /**
     * Return a copy of this list which does not contain any items with any of these params
     *
     * Raw SQL is not accepted, only actual field names can be passed
     *
     * @example $list = $list->excludeAny('Name', 'bob'); // exclude bob from list
     * @example $list = $list->excludeAny('Name', array('aziz', 'bob'); // exclude aziz and bob from list
     * @example $list = $list->excludeAny(array('Name'=>'bob, 'Age'=>21)); // exclude bob or Age 21
     * @example $list = $list->excludeAny(array('Name'=>'bob, 'Age'=>array(21, 43))); // exclude bob or Age 21 or 43
     * @example $list = $list->excludeAny(array('Name'=>array('bob','phil'), 'Age'=>array(21, 43)));
     *          // bob, phil, 21 or 43 would be excluded
     *
     * @param string|array
     * @param string [optional]
     *
     * @return $this
     */
    public function excludeAny()
    {
        $numberFuncArgs = count(func_get_args());
        $whereArguments = [];

        if ($numberFuncArgs == 1 && is_array(func_get_arg(0))) {
            $whereArguments = func_get_arg(0);
        } elseif ($numberFuncArgs == 2) {
            $whereArguments[func_get_arg(0)] = func_get_arg(1);
        } else {
            throw new InvalidArgumentException('Incorrect number of arguments passed to excludeAny()');
        }

        return $this->alterDataQuery(function (DataQuery $dataQuery) use ($whereArguments) {
            foreach ($whereArguments as $field => $value) {
                $filter = $this->createSearchFilter($field, $value);
                $filter->exclude($dataQuery);
            }
            return $dataQuery;
        });
    }

    /**
     * This method returns a copy of this list that does not contain any DataObjects that exists in $list
     *
     * The $list passed needs to contain the same dataclass as $this
     *
     * @param DataList $list
     * @return static
     * @throws InvalidArgumentException
     */
    public function subtract(DataList $list)
    {
        if ($this->dataClass() != $list->dataClass()) {
            throw new InvalidArgumentException('The list passed must have the same dataclass as this class');
        }

        return $this->alterDataQuery(function (DataQuery $query) use ($list) {
            $query->subtract($list->dataQuery());
        });
    }

    /**
     * Return a new DataList instance with an inner join clause added to this list's query.
     *
     * @param string $table Table name (unquoted and as escaped SQL)
     * @param string $onClause Escaped SQL statement, e.g. '"Table1"."ID" = "Table2"."ID"'
     * @param string $alias - if you want this table to be aliased under another name
     * @param int $order A numerical index to control the order that joins are added to the query; lower order values
     * will cause the query to appear first. The default is 20, and joins created automatically by the
     * ORM have a value of 10.
     * @param array $parameters Any additional parameters if the join is a parameterised subquery
     * @return static
     */
    public function innerJoin($table, $onClause, $alias = null, $order = 20, $parameters = [])
    {
        return $this->alterDataQuery(function (DataQuery $query) use ($table, $onClause, $alias, $order, $parameters) {
            $query->innerJoin($table, $onClause, $alias, $order, $parameters);
        });
    }

    /**
     * Return a new DataList instance with a left join clause added to this list's query.
     *
     * @param string $table Table name (unquoted and as escaped SQL)
     * @param string $onClause Escaped SQL statement, e.g. '"Table1"."ID" = "Table2"."ID"'
     * @param string $alias - if you want this table to be aliased under another name
     * @param int $order A numerical index to control the order that joins are added to the query; lower order values
     * will cause the query to appear first. The default is 20, and joins created automatically by the
     * ORM have a value of 10.
     * @param array $parameters Any additional parameters if the join is a parameterised subquery
     * @return static
     */
    public function leftJoin($table, $onClause, $alias = null, $order = 20, $parameters = [])
    {
        return $this->alterDataQuery(function (DataQuery $query) use ($table, $onClause, $alias, $order, $parameters) {
            $query->leftJoin($table, $onClause, $alias, $order, $parameters);
        });
    }

    /**
     * Return an array of the actual items that this DataList contains at this stage.
     * This is when the query is actually executed.
     *
     * @return array
     */
    public function toArray()
    {
        $results = [];

        foreach ($this as $item) {
            $results[] = $item;
        }

        return $results;
    }

    /**
     * Return this list as an array and every object it as an sub array as well
     *
     * @return array
     */
    public function toNestedArray()
    {
        $result = [];

        foreach ($this as $item) {
            $result[] = $item->toMap();
        }

        return $result;
    }

    /**
     * Walks the list using the specified callback
     *
     * @param callable $callback
     * @return $this
     */
    public function each($callback)
    {
        foreach ($this as $row) {
            $callback($row);
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
     * Returns a map of this list
     *
     * @param string $keyField - the 'key' field of the result array
     * @param string $titleField - the value field of the result array
     * @return Map
     */
    public function map($keyField = 'ID', $titleField = 'Title')
    {
        return new Map($this, $keyField, $titleField);
    }

    /**
     * Create a DataObject from the given SQL row
     * If called without $row['ID'] set, then a new object will be created rather than rehydrated.
     *
     * @param array $row
     * @return DataObject
     */
    public function createDataObject($row)
    {
        $class = $this->dataClass;

        if (empty($row['ClassName'])) {
            $row['ClassName'] = $class;
        }

        // Failover from RecordClassName to ClassName
        if (empty($row['RecordClassName'])) {
            $row['RecordClassName'] = $row['ClassName'];
        }

        // Instantiate the class mentioned in RecordClassName only if it exists, otherwise default to $this->dataClass
        if (class_exists($row['RecordClassName'] ?? '')) {
            $class = $row['RecordClassName'];
        }

        $creationType = empty($row['ID']) ? DataObject::CREATE_OBJECT : DataObject::CREATE_HYDRATED;

        $item = Injector::inst()->create($class, $row, $creationType, $this->getQueryParams());
        $this->setDataObjectEagerLoadedData($item);
        return $item;
    }

    private function setDataObjectEagerLoadedData(DataObject $item): void
    {
        // cache $item->ID at the top of this method to reduce calls to ViewableData::__get()
        $itemID = $item->ID;
        foreach (array_keys($this->eagerLoadedData) as $eagerLoadRelation) {
            list($dataClasses, $relations) = $this->getEagerLoadVariables($eagerLoadRelation);
            $dataClass = $dataClasses[count($dataClasses) - 2];
            $relation = $relations[count($relations) - 1];
            foreach (array_keys($this->eagerLoadedData[$eagerLoadRelation]) as $eagerLoadID) {
                $eagerLoadedData = $this->eagerLoadedData[$eagerLoadRelation][$eagerLoadID][$relation];
                if ($dataClass === $dataClasses[0]) {
                    if ($eagerLoadID === $itemID) {
                        $item->setEagerLoadedData($relation, $eagerLoadedData);
                    }
                } elseif ($dataClass === $dataClasses[1]) {
                    $relationData = $item->{$relations[1]}();
                    if ($relationData instanceof DataObject) {
                        if ($relationData->ID === $eagerLoadID) {
                            $subItem = $relationData;
                        } else {
                            $subItem = null;
                        }
                    } else {
                        $subItem = $item->{$relations[1]}()->find('ID', $eagerLoadID);
                    }
                    if ($subItem) {
                        $subItem->setEagerLoadedData($relations[2], $eagerLoadedData);
                    }
                } elseif ($dataClass === $dataClasses[2]) {
                    $relationData = $item->{$relations[1]}();
                    if ($relationData instanceof DataObject) {
                        $list = new ArrayList([$relationData]);
                    } else {
                        $list = $relationData;
                    }
                    foreach ($list as $subItem) {
                        $subRelationData = $subItem->{$relations[2]}();
                        if ($relationData instanceof DataObject) {
                            $subList = new ArrayList([$subRelationData]);
                        } else {
                            $subList = $subRelationData;
                        }
                        $subSubItem = $subList->find('ID', $eagerLoadID);
                        if ($subSubItem) {
                            $subSubItem->setEagerLoadedData($relations[3], $eagerLoadedData);
                        }
                    }
                }
            }
        }
    }

    /**
     * Get query parameters for this list.
     * These values will be assigned as query parameters to newly created objects from this list.
     *
     * @return array
     */
    public function getQueryParams()
    {
        return $this->dataQuery()->getQueryParams();
    }

    /**
     * Returns an Iterator for this DataList.
     * This function allows you to use DataLists in foreach loops
     */
    public function getIterator(): Traversable
    {
        foreach ($this->getFinalisedQuery() as $row) {
            yield $this->createDataObject($row);
        }

        // Re-set the finaliseQuery so that it can be re-executed
        $this->finalisedQuery = null;
        $this->eagerLoadedData = [];
    }

    /**
     * Returns the Query result for this DataList. Repeated calls will return
     * a cached result, unless the DataQuery underlying this list has been
     * modified
     *
     * @return SilverStripe\ORM\Connect\Query
     * @internal This API may change in minor releases
     */
    protected function getFinalisedQuery()
    {
        if (!$this->finalisedQuery) {
            $this->finalisedQuery = $this->executeQuery();
        }

        return $this->finalisedQuery;
    }

    private function getEagerLoadVariables(string $eagerLoadRelation): array
    {
        $schema = DataObject::getSchema();
        $relations = array_merge(['root'], explode('.', $eagerLoadRelation));
        $dataClasses = [$this->dataClass];
        $hasOneIDField = null;
        $belongsToIDField = null;
        $hasManyIDField = null;
        $manyManyLastComponent = null;
        for ($i = 0; $i < count($relations) - 1; $i++) {
            $parentDataClass = $dataClasses[$i];
            $relationName = $relations[$i + 1];
            $hasOneComponent = $schema->hasOneComponent($parentDataClass, $relationName);
            if ($hasOneComponent) {
                $dataClasses[] = $hasOneComponent;
                $hasOneIDField = $relations[$i + 1] . 'ID';
                continue;
            }
            $belongsToComponent = $schema->belongsToComponent($parentDataClass, $relationName);
            if ($belongsToComponent) {
                $dataClasses[] = $belongsToComponent;
                $belongsToIDField = $schema->getRemoteJoinField($parentDataClass, $relationName, 'belongs_to');
                continue;
            }
            $hasManyComponent = $schema->hasManyComponent($parentDataClass, $relationName);
            if ($hasManyComponent) {
                $dataClasses[] = $hasManyComponent;
                $hasManyIDField = $schema->getRemoteJoinField($parentDataClass, $relationName, 'has_many');
                continue;
            }
            // this works for both many_many and belongs_many_many
            $manyManyComponent = $schema->manyManyComponent($parentDataClass, $relationName);
            if ($manyManyComponent) {
                $dataClasses[] = $manyManyComponent['childClass'];
                $manyManyComponent['extraFields'] = $schema->manyManyExtraFieldsForComponent($parentDataClass, $relationName) ?: [];
                if (is_a($manyManyComponent['relationClass'], ManyManyThroughList::class, true)) {
                    $manyManyComponent['joinClass'] = $manyManyComponent['join'];
                    $manyManyComponent['join'] = $schema->baseDataTable($manyManyComponent['joinClass']);
                } else {
                    $manyManyComponent['joinClass'] = null;
                }
                $manyManyLastComponent = $manyManyComponent;
                continue;
            }
            throw new InvalidArgumentException("Invalid relation passed to eagerLoad() - $eagerLoadRelation");
        }
        return [$dataClasses, $relations, $hasOneIDField, $belongsToIDField, $hasManyIDField, $manyManyLastComponent];
    }

    private function executeQuery(): Query
    {
        $query = $this->dataQuery->query()->execute();
        $this->fetchEagerLoadRelations($query);
        return $query;
    }

    private function fetchEagerLoadRelations(Query $query): void
    {
        if (empty($this->eagerLoadRelations)) {
            return;
        }
        $topLevelIDs = $query->column('ID');
        if (empty($topLevelIDs)) {
            return;
        }
        $prevRelationArray = [];
        foreach ($this->eagerLoadRelations as $eagerLoadRelation) {
            list(
                $dataClasses,
                $relations,
                $hasOneIDField,
                $belongsToIDField,
                $hasManyIDField,
                $manyManyLastComponent
            ) = $this->getEagerLoadVariables($eagerLoadRelation);
            $parentDataClass = $dataClasses[count($dataClasses) - 2];
            $relationName = $relations[count($relations) - 1];
            $relationDataClass = $dataClasses[count($dataClasses) - 1];
            if ($parentDataClass === $this->dataClass()) {
                // When we're at "the top of a tree of nested relationships", we can just use the IDs from the query
                // This is important to do when handling multiple eager-loaded relationship trees.
                $parentIDs = $topLevelIDs;
            }
            // has_one
            if ($hasOneIDField) {
                list($prevRelationArray, $parentIDs) = $this->fetchEagerLoadHasOne(
                    $query,
                    $prevRelationArray,
                    $hasOneIDField,
                    $relationDataClass,
                    $eagerLoadRelation,
                    $relationName,
                    $parentDataClass
                );
            // belongs_to
            } elseif ($belongsToIDField) {
                list($prevRelationArray, $parentIDs) = $this->fetchEagerLoadBelongsTo(
                    $parentIDs,
                    $belongsToIDField,
                    $relationDataClass,
                    $eagerLoadRelation,
                    $relationName
                );
            // has_many
            } elseif ($hasManyIDField) {
                list($prevRelationArray, $parentIDs) = $this->fetchEagerLoadHasMany(
                    $parentIDs,
                    $hasManyIDField,
                    $relationDataClass,
                    $eagerLoadRelation,
                    $relationName
                );
            // many_many + belongs_many_many & many_many_through
            } elseif ($manyManyLastComponent) {
                list($prevRelationArray, $parentIDs) = $this->fetchEagerLoadManyMany(
                    $manyManyLastComponent,
                    $parentIDs,
                    $relationDataClass,
                    $eagerLoadRelation,
                    $relationName,
                    $parentDataClass
                );
            } else {
                throw new LogicException('Something went wrong with the eager loading');
            }
        }
    }

    private function fetchEagerLoadHasOne(
        Query $query,
        array $parentRecords,
        string $hasOneIDField,
        string $relationDataClass,
        string $eagerLoadRelation,
        string $relationName,
        string $parentDataClass
    ): array {
        $itemArray = [];
        $relationItemIDs = [];

        // It's a has_one directly on the records in THIS list
        if ($parentDataClass === $this->dataClass()) {
            foreach ($query as $itemData) {
                $itemArray[] = [
                    'ID' => $itemData['ID'],
                    $hasOneIDField => $itemData[$hasOneIDField]
                ];
                $relationItemIDs[] = $itemData[$hasOneIDField];
            }
        // It's a has_one on a list we've already eager-loaded
        } else {
            foreach ($parentRecords as $itemData) {
                $itemArray[] = [
                    'ID' => $itemData->ID,
                    $hasOneIDField => $itemData->$hasOneIDField
                ];
                $relationItemIDs[] = $itemData->$hasOneIDField;
            }
        }
        $relationArray = DataObject::get($relationDataClass)->byIDs($relationItemIDs)->toArray();
        foreach ($itemArray as $itemData) {
            foreach ($relationArray as $relationItem) {
                $eagerLoadID = $itemData['ID'];
                if ($relationItem->ID === $itemData[$hasOneIDField]) {
                    $this->eagerLoadedData[$eagerLoadRelation][$eagerLoadID][$relationName] = $relationItem;
                }
            }
        }
        return [$relationArray, $relationItemIDs];
    }

    private function fetchEagerLoadBelongsTo(
        array $parentIDs,
        string $belongsToIDField,
        string $relationDataClass,
        string $eagerLoadRelation,
        string $relationName
    ): array {
        // Get ALL of the items for this relation up front, for ALL of the parents
        // Fetched as an array to avoid sporadic additional queries when the DataList is looped directly
        $relationArray = DataObject::get($relationDataClass)->filter([$belongsToIDField => $parentIDs])->toArray();
        $relationItemIDs = [];

        // Store the children against the correct parent
        foreach ($relationArray as $relationItem) {
            $relationItemIDs[] = $relationItem->ID;
            $eagerLoadID = $relationItem->$belongsToIDField;
            $this->eagerLoadedData[$eagerLoadRelation][$eagerLoadID][$relationName] = $relationItem;
        }

        return [$relationArray, $relationItemIDs];
    }

    private function fetchEagerLoadHasMany(
        array $parentIDs,
        string $hasManyIDField,
        string $relationDataClass,
        string $eagerLoadRelation,
        string $relationName
    ): array {
        // Get ALL of the items for this relation up front, for ALL of the parents
        // Fetched as an array to avoid sporadic additional queries when the DataList is looped directly
        $relationArray = DataObject::get($relationDataClass)->filter([$hasManyIDField => $parentIDs])->toArray();
        $relationItemIDs = [];

        // Store the children in an ArrayList against the correct parent
        foreach ($relationArray as $relationItem) {
            $relationItemIDs[] = $relationItem->ID;
            $eagerLoadID = $relationItem->$hasManyIDField;
            if (!isset($this->eagerLoadedData[$eagerLoadRelation][$eagerLoadID][$relationName])) {
                $arrayList = ArrayList::create();
                $arrayList->setDataClass($relationDataClass);
                $this->eagerLoadedData[$eagerLoadRelation][$eagerLoadID][$relationName] = $arrayList;
            }
            $this->eagerLoadedData[$eagerLoadRelation][$eagerLoadID][$relationName]->push($relationItem);
        }

        return [$relationArray, $relationItemIDs];
    }

    private function fetchEagerLoadManyMany(
        array $manyManyLastComponent,
        array $parentIDs,
        string $relationDataClass,
        string $eagerLoadRelation,
        string $relationName,
        string $parentDataClass
    ): array {
        $parentIDField = $manyManyLastComponent['parentField'];
        $childIDField = $manyManyLastComponent['childField'];
        $joinTable = $manyManyLastComponent['join'];
        $extraFields = $manyManyLastComponent['extraFields'];
        $joinClass = $manyManyLastComponent['joinClass'];

        // Get the join records so we can correctly identify which children belong to which parents
        $joinRows = DB::query('SELECT * FROM "' . $joinTable . '" WHERE "' . $parentIDField . '" IN (' . implode(',', $parentIDs) . ')');

        // many_many_through
        if ($joinClass !== null) {
            $relationList = ManyManyThroughList::create(
                $relationDataClass,
                $joinClass,
                $childIDField,
                $parentIDField,
                $extraFields,
                $relationDataClass,
                $parentDataClass
            )->forForeignID($parentIDs);
        // many_many + belongs_many_many
        } else {
            $relationList = ManyManyList::create(
                $relationDataClass,
                $joinTable,
                $childIDField,
                $parentIDField,
                $extraFields
            )->forForeignID($parentIDs);
        }

        // Get ALL of the items for this relation up front, for ALL of the parents
        // Use a real RelationList here so that the extraFields and join record are correctly set for all relations
        // Fetched as a map so we can get the ID for all records up front (instead of in another nested loop)
        // Fetched after that as an array because for some reason that performs better in the loop
        // Note that "Me" is a method on ViewableData that returns $this - i.e. that is the actual DataObject record
        $relationArray = $relationList->map('ID', 'Me')->toArray();

        // Store the children in an ArrayList against the correct parent
        foreach ($joinRows as $row) {
            $parentID = $row[$parentIDField];
            $childID = $row[$childIDField];
            $relationItem = $relationArray[$childID];

            if (!isset($this->eagerLoadedData[$eagerLoadRelation][$parentID][$relationName])) {
                $arrayList = ArrayList::create();
                $arrayList->setDataClass($relationDataClass);
                $this->eagerLoadedData[$eagerLoadRelation][$parentID][$relationName] = $arrayList;
            }
            $this->eagerLoadedData[$eagerLoadRelation][$parentID][$relationName]->push($relationItem);
        }

        return [$relationArray, array_keys($relationArray)];
    }

    /**
     * Eager load relations for DataObjects in this DataList including nested relations
     *
     * Eager loading alleviates the N + 1 problem by querying the nested relationship tables before they are
     * needed using a single large `WHERE ID in ($ids)` SQL query instead of many `WHERE RelationID = $id` queries.
     *
     * You can speicify nested relations by using dot notation, and you can also pass in multiple relations.
     * When speicifying nested relations there is a maximum of 3 levels of relations allowed i.e. 2 dots
     *
     * Example:
     * $myDataList->eagerLoad('MyRelation.NestedRelation.EvenMoreNestedRelation', 'DifferentRelation')
     *
     * IMPORTANT: Calling eagerLoad() will cause any relations on DataObjects to be returned as an ArrayList
     * instead of a subclass of DataList such as HasManyList i.e. MyDataObject->MyHasManyRelation() returns an ArrayList
     */
    public function eagerLoad(...$relations): static
    {
        $arr = [];
        foreach ($relations as $relation) {
            $parts = explode('.', $relation);
            $count = count($parts);
            if ($count > 3) {
                $message = "Eager loading only supports up to 3 levels of nesting, passed $count levels - $relation";
                throw new InvalidArgumentException($message);
            }
            // Add each relation in the chain as its own entry to be eagerloaded
            // e.g. for "Players.Teams.Coaches" you'll have three entries:
            // "Players", "Players.Teams", and "Players.Teams.Coaches
            $usedParts = [];
            foreach ($parts as $part) {
                $usedParts[] = $part;
                $arr[] = implode('.', $usedParts);
            }
        }
        $this->eagerLoadRelations = array_unique(array_merge($this->eagerLoadRelations, $arr));
        return $this;
    }

    /**
     * Return the number of items in this DataList
     */
    public function count(): int
    {
        if ($this->finalisedQuery) {
            return $this->finalisedQuery->numRecords();
        }

        return $this->dataQuery->count();
    }

    /**
     * Return the maximum value of the given field in this DataList
     *
     * @param string $fieldName
     * @return mixed
     */
    public function max($fieldName)
    {
        return $this->dataQuery->max($fieldName);
    }

    /**
     * Return the minimum value of the given field in this DataList
     *
     * @param string $fieldName
     * @return mixed
     */
    public function min($fieldName)
    {
        return $this->dataQuery->min($fieldName);
    }

    /**
     * Return the average value of the given field in this DataList
     *
     * @param string $fieldName
     * @return mixed
     */
    public function avg($fieldName)
    {
        return $this->dataQuery->avg($fieldName);
    }

    /**
     * Return the sum of the values of the given field in this DataList
     *
     * @param string $fieldName
     * @return mixed
     */
    public function sum($fieldName)
    {
        return $this->dataQuery->sum($fieldName);
    }


    /**
     * Returns the first item in this DataList (instanceof DataObject)
     *
     * The object returned is not cached, unlike {@link DataObject::get_one()}
     *
     * @return DataObject|null
     */
    public function first()
    {
        foreach ($this->dataQuery->firstRow()->execute() as $row) {
            return $this->createDataObject($row);
        }
        return null;
    }

    /**
     * Returns the last item in this DataList (instanceof DataObject)
     *
     * The object returned is not cached, unlike {@link DataObject::get_one()}
     *
     * @return DataObject|null
     */
    public function last()
    {
        foreach ($this->dataQuery->lastRow()->execute() as $row) {
            return $this->createDataObject($row);
        }
        return null;
    }

    /**
     * Returns true if this DataList has items
     *
     * @return bool
     */
    public function exists()
    {
        return $this->dataQuery->exists();
    }

    /**
     * Find the first DataObject of this DataList where the given key = value
     *
     * The object returned is not cached, unlike {@link DataObject::get_one()}
     *
     * @param string $key
     * @param string $value
     * @return DataObject|null
     */
    public function find($key, $value)
    {
        return $this->filter($key, $value)->first();
    }

    /**
     * Restrict the columns to fetch into this DataList
     *
     * @param array $queriedColumns
     * @return static
     */
    public function setQueriedColumns($queriedColumns)
    {
        return $this->alterDataQuery(function (DataQuery $query) use ($queriedColumns) {
            $query->setQueriedColumns($queriedColumns);
        });
    }

    /**
     * Filter this list to only contain the given Primary IDs
     *
     * @param array $ids Array of integers
     * @return $this
     */
    public function byIDs($ids)
    {
        return $this->filter('ID', $ids);
    }

    /**
     * Return the first DataObject with the given ID
     *
     * The object returned is not cached, unlike {@link DataObject::get_by_id()}
     *
     * @param int $id
     * @return DataObject|null
     */
    public function byID($id)
    {
        return $this->filter('ID', $id)->first();
    }

    /**
     * Returns an array of a single field value for all items in the list.
     *
     * @param string $colName
     * @return array
     */
    public function column($colName = "ID")
    {
        if ($this->finalisedQuery) {
            $finalisedQuery = clone $this->finalisedQuery;
            return $finalisedQuery->distinct(false)->column($colName);
        }

        $dataQuery = clone $this->dataQuery;
        return $dataQuery->distinct(false)->column($colName);
    }

    /**
     * Returns a unique array of a single field value for all items in the list.
     *
     * @param string $colName
     * @return array
     */
    public function columnUnique($colName = "ID")
    {
        return $this->dataQuery->distinct(true)->column($colName);
    }

    // Member altering methods

    /**
     * Sets the ComponentSet to be the given ID list.
     * Records will be added and deleted as appropriate.
     *
     * @param array $idList List of IDs.
     */
    public function setByIDList($idList)
    {
        $has = [];

        // Index current data
        foreach ($this->column() as $id) {
            $has[$id] = true;
        }

        // Keep track of items to delete
        $itemsToDelete = $has;

        // add items in the list
        // $id is the database ID of the record
        if ($idList) {
            foreach ($idList as $id) {
                unset($itemsToDelete[$id]);
                if ($id && !isset($has[$id])) {
                    $this->add($id);
                }
            }
        }

        // Remove any items that haven't been mentioned
        $this->removeMany(array_keys($itemsToDelete ?? []));
    }

    /**
     * Returns an array with both the keys and values set to the IDs of the records in this list.
     * Does not respect sort order. Use ->column("ID") to get an ID list with the current sort.
     *
     * @return array
     */
    public function getIDList()
    {
        $ids = $this->column("ID");
        return $ids ? array_combine($ids, $ids) : [];
    }

    /**
     * Returns a HasManyList or ManyMany list representing the querying of a relation across all
     * objects in this data list.  For it to work, the relation must be defined on the data class
     * that you used to create this DataList.
     *
     * Example: Get members from all Groups:
     *
     *     DataList::Create(\SilverStripe\Security\Group::class)->relation("Members")
     *
     * @param string $relationName
     * @return HasManyList|ManyManyList
     */
    public function relation($relationName)
    {
        $ids = $this->column('ID');
        $singleton = DataObject::singleton($this->dataClass);
        /** @var HasManyList|ManyManyList $relation */
        $relation = $singleton->$relationName($ids);
        return $relation;
    }

    public function dbObject($fieldName)
    {
        return singleton($this->dataClass)->dbObject($fieldName);
    }

    /**
     * Add a number of items to the component set.
     *
     * @param array $items Items to add, as either DataObjects or IDs.
     * @return $this
     */
    public function addMany($items)
    {
        foreach ($items as $item) {
            $this->add($item);
        }
        return $this;
    }

    /**
     * Remove the items from this list with the given IDs
     *
     * @param array $idList
     * @return $this
     */
    public function removeMany($idList)
    {
        foreach ($idList as $id) {
            $this->removeByID($id);
        }
        return $this;
    }

    /**
     * Remove every element in this DataList matching the given $filter.
     *
     * @param string|array $filter - a sql type where filter
     * @return $this
     */
    public function removeByFilter($filter)
    {
        foreach ($this->where($filter) as $item) {
            $this->remove($item);
        }
        return $this;
    }

    /**
     * Shuffle the datalist using a random function provided by the SQL engine
     *
     * @return $this
     */
    public function shuffle()
    {
        return $this->orderBy(DB::get_conn()->random());
    }

    /**
     * Remove every element in this DataList.
     *
     * @return $this
     */
    public function removeAll()
    {
        foreach ($this as $item) {
            $this->remove($item);
        }
        return $this;
    }

    /**
     * This method are overloaded by HasManyList and ManyMany list to perform more sophisticated
     * list manipulation
     *
     * @param mixed $item
     */
    public function add($item)
    {
        // Nothing needs to happen by default
        // TO DO: If a filter is given to this data list then
    }

    /**
     * Return a new item to add to this DataList.
     *
     * @todo This doesn't factor in filters.
     * @param array $initialFields
     * @return DataObject
     */
    public function newObject($initialFields = null)
    {
        $class = $this->dataClass;
        return Injector::inst()->create($class, $initialFields, false);
    }

    /**
     * Remove this item by deleting it
     *
     * @param DataObject $item
     * @todo Allow for amendment of this behaviour - for example, we can remove an item from
     * an "ActiveItems" DataList by changing the status to inactive.
     */
    public function remove($item)
    {
        // By default, we remove an item from a DataList by deleting it.
        $this->removeByID($item->ID);
    }

    /**
     * Remove an item from this DataList by ID
     *
     * @param int $itemID The primary ID
     */
    public function removeByID($itemID)
    {
        $item = $this->byID($itemID);

        if ($item) {
            $item->delete();
        }
    }

    /**
     * Reverses a list of items.
     *
     * @return static
     */
    public function reverse()
    {
        return $this->alterDataQuery(function (DataQuery $query) {
            $query->reverseSort();
        });
    }

    /**
     * Returns whether an item with $key exists
     */
    public function offsetExists(mixed $key): bool
    {
        return ($this->limit(1, $key)->first() != null);
    }

    /**
     * Returns item stored in list with index $key
     *
     * The object returned is not cached, unlike {@link DataObject::get_one()}
     */
    public function offsetGet(mixed $key): ?DataObject
    {
        return $this->limit(1, $key)->first();
    }

    /**
     * Set an item with the key in $key
     * @throws BadMethodCallException
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        throw new BadMethodCallException("Can't alter items in a DataList using array-access");
    }

    /**
     * Unset an item with the key in $key
     *
     * @throws BadMethodCallException
     */
    public function offsetUnset(mixed $key): void
    {
        throw new BadMethodCallException("Can't alter items in a DataList using array-access");
    }

    /**
     * Iterate over this DataList in "chunks". This will break the query in smaller subsets and avoid loading the entire
     * result set in memory at once. Beware not to perform any operations on the results that might alter the return
     * order. Otherwise, you might break subsequent chunks.
     *
     * You also can not define a custom limit or offset when using the chunk method.
     *
     * @param int $chunkSize
     * @throws InvalidArgumentException If `$chunkSize` has an invalid size.
     * @return Generator|DataObject[]
     */
    public function chunkedFetch(int $chunkSize = 1000): iterable
    {
        if ($chunkSize < 1) {
            throw new InvalidArgumentException(sprintf(
                '%s::%s: chunkSize must be greater than or equal to 1',
                __CLASS__,
                __METHOD__
            ));
        }

        $currentChunk = 0;

        // Keep looping until we run out of chunks
        while ($chunk = $this->limit($chunkSize, $chunkSize * $currentChunk)) {
            // Loop over all the item in our chunk
            $count = 0;
            foreach ($chunk as $item) {
                $count++;
                yield $item;
            }

            if ($count < $chunkSize) {
                // If our last chunk had less item than our chunkSize, we've reach the end.
                break;
            }

            $currentChunk++;
        }
    }
}
