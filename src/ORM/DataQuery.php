<?php

namespace SilverStripe\ORM;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\Connect\Query;
use SilverStripe\ORM\Queries\SQLConditionGroup;
use SilverStripe\ORM\Queries\SQLSelect;
use InvalidArgumentException;

/**
 * An object representing a query of data from the DataObject's supporting database.
 * Acts as a wrapper over {@link SQLSelect} and performs all of the query generation.
 * Used extensively by {@link DataList}.
 *
 * Unlike DataList, modifiers on DataQuery modify the object rather than returning a clone.
 * DataList is immutable, DataQuery is mutable.
 */
class DataQuery
{

    use Extensible;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var SQLSelect
     */
    protected $query;

    /**
     * Map of all field names to an array of conflicting column SQL
     *
     * E.g.
     * [
     *   'Title' => [
     *     '"MyTable"."Title"',
     *     '"AnotherTable"."Title"',
     *   ]
     * ]
     *
     * @var array
     */
    protected $collidingFields = [];

    /**
     * If true, collisions are allowed for statements aliased as db columns
     */
    private $allowCollidingFieldStatements = false;

    /**
     * Allows custom callback to be registered before getFinalisedQuery is called.
     *
     * @var DataQueryManipulator[]
     */
    protected $dataQueryManipulators = [];

    private $queriedColumns = null;

    /**
     * @var bool
     */
    private $queryFinalised = false;

    protected $querySubclasses = true;

    protected $filterByClassName = true;

    /**
     * Create a new DataQuery.
     *
     * @param string $dataClass The name of the DataObject class that you wish to query
     */
    public function __construct($dataClass)
    {
        $this->dataClass = $dataClass;
        $this->initialiseQuery();
    }

    /**
     * Clone this object
     */
    public function __clone()
    {
        $this->query = clone $this->query;
    }

    /**
     * Return the {@link DataObject} class that is being queried.
     *
     * @return string
     */
    public function dataClass()
    {
        return $this->dataClass;
    }

    /**
     * Return the {@link SQLSelect} object that represents the current query; note that it will
     * be a clone of the object.
     *
     * @return SQLSelect
     */
    public function query()
    {
        return $this->getFinalisedQuery();
    }


    /**
     * Remove a filter from the query
     *
     * @param string|array $fieldExpression The predicate of the condition to remove
     * (ignoring parameters). The expression will be considered a match if it's
     * contained within any other predicate.
     * @return $this
     */
    public function removeFilterOn($fieldExpression)
    {
        $matched = false;

        // If given a parameterised condition extract only the condition
        if (is_array($fieldExpression)) {
            reset($fieldExpression);
            $fieldExpression = key($fieldExpression ?? []);
        }

        $where = $this->query->getWhere();
        // Iterate through each condition
        foreach ($where as $i => $condition) {
            // Rewrite condition groups as plain conditions before comparison
            if ($condition instanceof SQLConditionGroup) {
                $predicate = $condition->conditionSQL($parameters);
                $condition = [$predicate => $parameters];
            }

            // As each condition is a single length array, do a single
            // iteration to extract the predicate and parameters
            foreach ($condition as $predicate => $parameters) {
                // @see SQLSelect::addWhere for why this is required here
                if (strpos($predicate ?? '', $fieldExpression ?? '') !== false) {
                    unset($where[$i]);
                    $matched = true;
                }
                // Enforce single-item condition predicate => parameters structure
                break;
            }
        }

        // set the entire where clause back, but clear the original one first
        if ($matched) {
            $this->query->setWhere($where);
        } else {
            throw new InvalidArgumentException("Couldn't find $fieldExpression in the query filter.");
        }

        return $this;
    }

    /**
     * Set up the simplest initial query
     */
    protected function initialiseQuery()
    {
        // Join on base table and let lazy loading join subtables
        $baseClass = DataObject::getSchema()->baseDataClass($this->dataClass());
        if (!$baseClass) {
            throw new InvalidArgumentException("DataQuery::create() Can't find data classes for '{$this->dataClass}'");
        }

        // Build our initial query
        $this->query = new SQLSelect([]);
        $this->query->setDistinct(true);

        if ($sort = singleton($this->dataClass)->config()->get('default_sort')) {
            $this->sort($sort);
        }

        $baseTable = DataObject::getSchema()->tableName($baseClass);
        $this->query->setFrom("\"{$baseTable}\"");

        $obj = Injector::inst()->get($baseClass);
        $obj->extend('augmentDataQueryCreation', $this->query, $this);
    }

    /**
     * @param array $queriedColumns
     * @return $this
     */
    public function setQueriedColumns($queriedColumns)
    {
        $this->queriedColumns = $queriedColumns;
        return $this;
    }

    /**
     * Ensure that the query is ready to execute.
     *
     * @param array|null $queriedColumns Any columns to filter the query by
     * @return SQLSelect The finalised sql query
     */
    public function getFinalisedQuery($queriedColumns = null)
    {
        if (!$queriedColumns) {
            $queriedColumns = $this->queriedColumns;
        }
        if ($queriedColumns) {
            // Add fixed fields to the query
            // ID is a special case and gets added separately later
            $fixedFields = DataObject::config()->uninherited('fixed_fields');
            unset($fixedFields['ID']);
            $queriedColumns = array_merge($queriedColumns, array_keys($fixedFields));
        }
        $query = clone $this->query;

        // Apply manipulators before finalising query
        foreach ($this->getDataQueryManipulators() as $manipulator) {
            $manipulator->beforeGetFinalisedQuery($this, $queriedColumns, $query);
        }

        $schema = DataObject::getSchema();
        $baseDataClass = $schema->baseDataClass($this->dataClass());
        $baseIDColumn = $schema->sqlColumnForField($baseDataClass, 'ID');
        $ancestorClasses = ClassInfo::ancestry($this->dataClass(), true);

        // Generate the list of tables to iterate over and the list of columns required
        // by any existing where clauses. This second step is skipped if we're fetching
        // the whole dataobject as any required columns will get selected regardless.
        if ($queriedColumns) {
            // Specifying certain columns allows joining of child tables
            $tableClasses = ClassInfo::dataClassesFor($this->dataClass);

            // Ensure that any filtered columns are included in the selected columns
            foreach ($query->getWhereParameterised($parameters) as $where) {
                // Check for any columns in the form '"Column" = ?' or '"Table"."Column"' = ?
                if (preg_match_all(
                    '/(?:"(?<table>[^"]+)"\.)?"(?<column>[^"]+)"(?:[^\.]|$)/',
                    $where ?? '',
                    $matches,
                    PREG_SET_ORDER
                )) {
                    foreach ($matches as $match) {
                        $column = $match['column'];
                        if (!in_array($column, $queriedColumns ?? [])) {
                            $queriedColumns[] = $column;
                        }
                    }
                }
            }
        } else {
            $tableClasses = $ancestorClasses;
        }

        // Iterate over the tables and check what we need to select from them. If any selects are made (or the table is
        // required for a select)
        foreach ($tableClasses as $tableClass) {
            // Determine explicit columns to select
            $selectColumns = null;
            if ($queriedColumns) {
                // Restrict queried columns to that on the selected table
                $tableFields = $schema->databaseFields($tableClass, false);
                unset($tableFields['ID']);
                $selectColumns = array_intersect($queriedColumns ?? [], array_keys($tableFields ?? []));
            }

            // If this is a subclass without any explicitly requested columns, omit this from the query
            if (!in_array($tableClass, $ancestorClasses ?? []) && empty($selectColumns)) {
                continue;
            }

            // Select necessary columns (unless an explicitly empty array)
            if ($selectColumns !== []) {
                $this->selectColumnsFromTable($query, $tableClass, $selectColumns);
            }

            // Join if not the base table
            if ($tableClass !== $baseDataClass) {
                $tableName = $schema->tableName($tableClass);
                $query->addLeftJoin(
                    $tableName,
                    "\"{$tableName}\".\"ID\" = {$baseIDColumn}",
                    $tableName,
                    10
                );
            }
        }

        // Resolve colliding fields
        if ($this->collidingFields) {
            foreach ($this->collidingFields as $collisionField => $collisions) {
                $caseClauses = [];
                $lastClauses = [];
                foreach ($collisions as $collision) {
                    if (preg_match('/^"(?<table>[^"]+)"\./', $collision ?? '', $matches)) {
                        $collisionTable = $matches['table'];
                        $collisionClass = $schema->tableClass($collisionTable);
                        if ($collisionClass) {
                            $collisionClassColumn = $schema->sqlColumnForField($collisionClass, 'ClassName');
                            $collisionClasses = ClassInfo::subclassesFor($collisionClass);
                            $collisionClassesSQL = implode(', ', Convert::raw2sql($collisionClasses, true));
                            // Only add clause if this is already joined to avoid "Unknown column 'ClassName'" error
                            $collisionTableForClassName = $schema->tableForField($collisionClass, 'ClassName');
                            if (array_key_exists($collisionTableForClassName, $query->getFrom())) {
                                $caseClauses[] = "WHEN {$collisionClassColumn} IN ({$collisionClassesSQL}) THEN $collision";
                            }
                        }
                    } else {
                        if ($this->getAllowCollidingFieldStatements()) {
                            $lastClauses[] = "WHEN $collision IS NOT NULL THEN $collision";
                        } else {
                            user_error("Bad collision item '$collision'", E_USER_WARNING);
                        }
                    }
                }
                $caseClauses = array_merge($caseClauses, $lastClauses);
                if (!empty($caseClauses)) {
                    $query->selectField("CASE " . implode(" ", $caseClauses) . " ELSE NULL END", $collisionField);
                }
            }
        }


        if ($this->filterByClassName) {
            // If querying the base class, don't bother filtering on class name
            if ($this->dataClass != $baseDataClass) {
                // Get the ClassName values to filter to
                $classNames = ClassInfo::subclassesFor($this->dataClass);
                $classNamesPlaceholders = DB::placeholders($classNames);
                $baseClassColumn = $schema->sqlColumnForField($baseDataClass, 'ClassName');
                $query->addWhere([
                    "{$baseClassColumn} IN ($classNamesPlaceholders)" => $classNames
                ]);
            }
        }

        // Select ID
        $query->selectField($baseIDColumn, "ID");

        // Select RecordClassName
        $baseClassColumn = $schema->sqlColumnForField($baseDataClass, 'ClassName');
        $query->selectField(
            "
			CASE WHEN {$baseClassColumn} IS NOT NULL THEN {$baseClassColumn}
			ELSE " . Convert::raw2sql($baseDataClass, true) . " END",
            "RecordClassName"
        );

        $obj = Injector::inst()->get($this->dataClass);
        $obj->extend('augmentSQL', $query, $this);

        $this->ensureSelectContainsOrderbyColumns($query);

        // Apply post-finalisation manipulations
        foreach ($this->getDataQueryManipulators() as $manipulator) {
            $manipulator->afterGetFinalisedQuery($this, $queriedColumns, $query);
        }

        return $query;
    }

    /**
     * Ensure that if a query has an order by clause, those columns are present in the select.
     *
     * @param SQLSelect $query
     * @param array $originalSelect
     */
    protected function ensureSelectContainsOrderbyColumns($query, $originalSelect = [])
    {
        if ($orderby = $query->getOrderBy()) {
            $newOrderby = [];
            foreach ($orderby as $k => $dir) {
                $newOrderby[$k] = $dir;

                // don't touch functions in the ORDER BY or public function calls
                // selected as fields
                if (strpos($k ?? '', '(') !== false) {
                    continue;
                }

                $col = str_replace('"', '', trim($k ?? ''));
                $parts = explode('.', $col ?? '');

                // Pull through SortColumn references from the originalSelect variables
                if (preg_match('/_SortColumn/', $col ?? '')) {
                    if (isset($originalSelect[$col])) {
                        $query->selectField($originalSelect[$col], $col);
                    }
                    continue;
                }

                if (count($parts ?? []) == 1) {
                    // Get expression for sort value
                    $qualCol = "\"{$parts[0]}\"";
                    $table = DataObject::getSchema()->tableForField($this->dataClass(), $parts[0]);
                    if ($table) {
                        $qualCol = "\"{$table}\".{$qualCol}";
                    }

                    // remove original sort
                    unset($newOrderby[$k]);

                    // add new columns sort
                    $newOrderby[$qualCol] = $dir;

                    // To-do: Remove this if block once SQLSelect::$select has been refactored to store getSelect()
                    // format internally; then this check can be part of selectField()
                    $selects = $query->getSelect();
                    if (!isset($selects[$col]) && !in_array($qualCol, $selects ?? [])) {
                        // Use the original select if possible.
                        if (array_key_exists($col, $originalSelect ?? [])) {
                            $query->selectField($originalSelect[$col], $col);
                        } else {
                            $query->selectField($qualCol);
                        }
                    }
                } else {
                    $qualCol = '"' . implode('"."', $parts) . '"';

                    if (!in_array($qualCol, $query->getSelect() ?? [])) {
                        unset($newOrderby[$k]);

                        // Find the first free "_SortColumnX" slot
                        // and assign it to $key
                        $i = 0;
                        while (isset($newOrderby[$key = "\"_SortColumn$i\""]) || isset($orderby[$key = "\"_SortColumn$i\""])) {
                            ++$i;
                        }

                        $newOrderby[$key] = $dir;
                        $query->selectField($qualCol, "_SortColumn$i");
                    }
                }
            }

            $query->setOrderBy($newOrderby);
        }
    }

    /**
     * Execute the query and return the result as {@link SS_Query} object.
     *
     * @return Query
     */
    public function execute()
    {
        return $this->getFinalisedQuery()->execute();
    }

    /**
     * Return this query's SQL
     *
     * @param array $parameters Out variable for parameters required for this query
     * @return string The resulting SQL query (may be paramaterised)
     */
    public function sql(&$parameters = [])
    {
        return $this->getFinalisedQuery()->sql($parameters);
    }

    /**
     * Return the number of records in this query.
     * Note that this will issue a separate SELECT COUNT() query.
     *
     * @return int
     */
    public function count()
    {
        $quotedColumn = DataObject::getSchema()->sqlColumnForField($this->dataClass(), 'ID');
        return $this->getFinalisedQuery()->count("DISTINCT {$quotedColumn}");
    }

    /**
     * Return whether this dataquery will have records. This will use `EXISTS` statements in SQL which are more
     * performant - especially when used in combination with indexed columns (that you're filtering on)
     *
     * @return bool
     */
    public function exists(): bool
    {
        // Grab a statement selecting "everything" - the engine shouldn't care what's being selected in an "EXISTS"
        // statement anyway
        $statement = $this->getFinalisedQuery();

        // Clear distinct, and order as it's not relevant for an exists query
        $statement->setDistinct(false);
        $statement->setOrderBy(null);

        // We can remove grouping if there's no "having" that might be relying on an aggregate
        // Additionally, the columns being selected no longer matter
        $having = $statement->getHaving();
        if (empty($having)) {
            $statement->setSelect('*');
            $statement->setGroupBy(null);
        }

        // Wrap the whole thing in an "EXISTS"
        $sql = 'SELECT CASE WHEN EXISTS(' . $statement->sql($params) . ') THEN 1 ELSE 0 END';
        $result = DB::prepared_query($sql, $params);
        $row = $result->record();
        $result = reset($row);

        // Checking for 't' supports PostgreSQL before silverstripe/postgresql@2.2
        return $result === true || $result === 1 || $result === '1' || $result === 't';
    }

    /**
     * Return the maximum value of the given field in this DataList
     *
     * @param string $field Unquoted database column name. Will be ANSI quoted
     * automatically so must not contain double quotes.
     * @return string
     */
    public function max($field)
    {
        $table = DataObject::getSchema()->tableForField($this->dataClass, $field);
        if (!$table) {
            return $this->aggregate("MAX(\"$field\")");
        }
        return $this->aggregate("MAX(\"$table\".\"$field\")");
    }

    /**
     * Return the minimum value of the given field in this DataList
     *
     * @param string $field Unquoted database column name. Will be ANSI quoted
     * automatically so must not contain double quotes.
     * @return string
     */
    public function min($field)
    {
        $table = DataObject::getSchema()->tableForField($this->dataClass, $field);
        if (!$table) {
            return $this->aggregate("MIN(\"$field\")");
        }
        return $this->aggregate("MIN(\"$table\".\"$field\")");
    }

    /**
     * Return the average value of the given field in this DataList
     *
     * @param string $field Unquoted database column name. Will be ANSI quoted
     * automatically so must not contain double quotes.
     * @return string
     */
    public function avg($field)
    {
        $table = DataObject::getSchema()->tableForField($this->dataClass, $field);
        if (!$table) {
            return $this->aggregate("AVG(\"$field\")");
        }
        return $this->aggregate("AVG(\"$table\".\"$field\")");
    }

    /**
     * Return the sum of the values of the given field in this DataList
     *
     * @param string $field Unquoted database column name. Will be ANSI quoted
     * automatically so must not contain double quotes.
     * @return string
     */
    public function sum($field)
    {
        $table = DataObject::getSchema()->tableForField($this->dataClass, $field);
        if (!$table) {
            return $this->aggregate("SUM(\"$field\")");
        }
        return $this->aggregate("SUM(\"$table\".\"$field\")");
    }

    /**
     * Runs a raw aggregate expression.  Please handle escaping yourself
     *
     * @param string $expression An aggregate expression, such as 'MAX("Balance")', or a set of them
     * (as an escaped SQL statement)
     * @return string
     */
    public function aggregate($expression)
    {
        return $this->getFinalisedQuery()->aggregate($expression)->execute()->value();
    }

    /**
     * Return the first row that would be returned by this full DataQuery
     * Note that this will issue a separate SELECT ... LIMIT 1 query.
     *
     * @return SQLSelect
     */
    public function firstRow()
    {
        return $this->getFinalisedQuery()->firstRow();
    }

    /**
     * Return the last row that would be returned by this full DataQuery
     * Note that this will issue a separate SELECT ... LIMIT query.
     *
     * @return SQLSelect
     */
    public function lastRow()
    {
        return $this->getFinalisedQuery()->lastRow();
    }

    /**
     * Update the SELECT clause of the query with the columns from the given table
     *
     * @param SQLSelect $query
     * @param string $tableClass Class to select from
     * @param array $columns
     */
    protected function selectColumnsFromTable(SQLSelect &$query, $tableClass, $columns = null)
    {
        // Add SQL for multi-value fields
        $schema = DataObject::getSchema();
        $databaseFields = $schema->databaseFields($tableClass, false);
        $compositeFields = $schema->compositeFields($tableClass, false);
        $tableName = $schema->tableName($tableClass);
        unset($databaseFields['ID']);
        foreach ($databaseFields as $k => $v) {
            if ((is_null($columns) || in_array($k, $columns ?? [])) && !isset($compositeFields[$k])) {
                // Update $collidingFields if necessary
                $expressionForField = $query->expressionForField($k);
                $quotedField = $schema->sqlColumnForField($tableClass, $k);
                if ($expressionForField) {
                    if (!isset($this->collidingFields[$k])) {
                        $this->collidingFields[$k] = [$expressionForField];
                    }
                    $this->collidingFields[$k][] = $quotedField;
                } else {
                    $query->selectField($quotedField, $k);
                }
                $dbO = Injector::inst()->create($v, $k);
                $dbO->setTable($tableName);
                $dbO->addToQuery($query);
            }
        }
        foreach ($compositeFields as $k => $v) {
            if ((is_null($columns) || in_array($k, $columns ?? [])) && $v) {
                $dbO = Injector::inst()->create($v, $k);
                $dbO->setTable($tableName);
                $dbO->addToQuery($query);
            }
        }
    }

    /**
     * Append a GROUP BY clause to this query.
     *
     * @param string $groupby Escaped SQL statement
     * @return $this
     */
    public function groupby($groupby)
    {
        $this->query->addGroupBy($groupby);
        return $this;
    }

    /**
     * Append a HAVING clause to this query.
     *
     * @param mixed $having Predicate(s) to set, as escaped SQL statements or parameterised queries
     * @return $this
     */
    public function having($having)
    {
        $this->query->addHaving($having);
        return $this;
    }

    /**
     * Add a query to UNION with.
     *
     * @param string|null $type One of the SQLSelect::UNION_ALL or SQLSelect::UNION_DISTINCT constants - or null for a default union
     */
    public function union(DataQuery|SQLSelect $query, ?string $type = null): static
    {
        if ($query instanceof DataQuery) {
            $query = $query->query();
        }
        $this->query->addUnion($query, $type);
        return $this;
    }

    /**
     * Create a disjunctive subgroup.
     *
     * That is a subgroup joined by OR
     *
     * @param string $clause
     * @return DataQuery_SubGroup
     */
    public function disjunctiveGroup()
    {
        // using func_get_args to add a new param while retaining BC
        // @deprecated - add a new param for CMS 6 - string $clause = 'WHERE'
        $clause = 'WHERE';
        $args = func_get_args();
        if (count($args) > 0) {
            $clause = $args[0];
        }
        return new DataQuery_SubGroup($this, 'OR', $clause);
    }

    /**
     * Create a conjunctive subgroup
     *
     * That is a subgroup joined by AND
     *
     * @param string $clause
     * @return DataQuery_SubGroup
     */
    public function conjunctiveGroup()
    {
        // using func_get_args to add a new param while retaining BC
        // @deprecated - add a new param for CMS 6 - string $clause = 'WHERE'
        $clause = 'WHERE';
        $args = func_get_args();
        if (count($args) > 0) {
            $clause = $args[0];
        }
        return new DataQuery_SubGroup($this, 'AND', $clause);
    }

    /**
     * Adds a Common Table Expression (CTE), aka WITH clause.
     *
     * Use of this method should usually be within a conditional check against DB::get_conn()->supportsCteQueries().
     *
     * @param string $name The name of the WITH clause, which can be referenced in any queries UNIONed to the $query
     * and in this query directly, as though it were a table name.
     * @param string[] $cteFields Aliases for any columns selected in $query which can be referenced in any queries
     * UNIONed to the $query and in this query directly, as though they were columns in a real table.
     * NOTE: If $query is a DataQuery, then cteFields must be the names of real columns on that DataQuery's data class.
     */
    public function with(string $name, DataQuery|SQLSelect $query, array $cteFields = [], bool $recursive = false): static
    {
        $schema = DataObject::getSchema();

        // If the query is a DataQuery, make sure all manipulators, joins, etc are applied
        if ($query instanceof DataQuery) {
            $cteDataClass = $query->dataClass();
            $query = $query->query();
            // DataQuery wants to select ALL columns by default,
            // but if we're setting cteFields then we only want to select those fields.
            if (!empty($cteFields)) {
                $selectFields = array_map(fn($colName) => $schema->sqlColumnForField($cteDataClass, $colName), $cteFields);
                $query->setSelect($selectFields);
            }
        }

        // Add the WITH clause
        $this->query->addWith($name, $query, $cteFields, $recursive);

        return $this;
    }

    /**
     * Adds a WHERE clause.
     *
     * @see SQLSelect::addWhere() for syntax examples, although DataQuery
     * won't expand multiple arguments as SQLSelect does.
     *
     * @param string|array|SQLConditionGroup $filter Predicate(s) to set, as escaped SQL statements or
     * paramaterised queries
     * @return $this
     */
    public function where($filter)
    {
        if ($filter) {
            $this->query->addWhere($filter);
        }
        return $this;
    }

    /**
     * Append a WHERE with OR.
     *
     * @see SQLSelect::addWhere() for syntax examples, although DataQuery
     * won't expand multiple method arguments as SQLSelect does.
     *
     * @param string|array|SQLConditionGroup $filter Predicate(s) to set, as escaped SQL statements or
     * paramaterised queries
     * @return $this
     */
    public function whereAny($filter)
    {
        if ($filter) {
            $this->query->addWhereAny($filter);
        }
        return $this;
    }

    /**
     * Set the ORDER BY clause of this query
     *
     * Note: while the similarly named DataList::sort() does not allow raw SQL, DataQuery::sort() does allow it
     *
     * Raw SQL can be vulnerable to SQL injection attacks if used incorrectly, so it's preferable not to use it
     *
     * @see SQLSelect::orderby()
     *
     * @param string $sort Column to sort on (escaped SQL statement)
     * @param string $direction Direction ("ASC" or "DESC", escaped SQL statement)
     * @param bool $clear Clear existing values
     * @return $this
     *
     */
    public function sort($sort = null, $direction = null, $clear = true)
    {
        if ($clear) {
            $this->query->setOrderBy($sort, $direction);
        } else {
            $this->query->addOrderBy($sort, $direction);
        }

        return $this;
    }

    /**
     * Reverse order by clause
     *
     * @return $this
     */
    public function reverseSort()
    {
        $this->query->reverseOrderBy();
        return $this;
    }

    /**
     * Set the limit of this query.
     */
    public function limit(?int $limit, int $offset = 0): static
    {
        $this->query->setLimit($limit, $offset);
        return $this;
    }

    /**
     * Set whether this query should be distinct or not.
     *
     * @param bool $value
     * @return $this
     */
    public function distinct($value)
    {
        $this->query->setDistinct($value);
        return $this;
    }

    /**
     * Add an INNER JOIN clause to this query.
     *
     * @param string $table The unquoted table name to join to.
     * @param string $onClause The filter for the join (escaped SQL statement)
     * @param string $alias An optional alias name (unquoted)
     * @param int $order A numerical index to control the order that joins are added to the query; lower order values
     * will cause the query to appear first. The default is 20, and joins created automatically by the
     * ORM have a value of 10.
     * @param array $parameters Any additional parameters if the join is a parameterised subquery
     * @return $this
     */
    public function innerJoin($table, $onClause, $alias = null, $order = 20, $parameters = [])
    {
        if ($table) {
            $this->query->addInnerJoin($table, $onClause, $alias, $order, $parameters);
        }
        return $this;
    }

    /**
     * Add a LEFT JOIN clause to this query.
     *
     * @param string $table The unquoted table to join to.
     * @param string $onClause The filter for the join (escaped SQL statement).
     * @param string $alias An optional alias name (unquoted)
     * @param int $order A numerical index to control the order that joins are added to the query; lower order values
     * will cause the query to appear first. The default is 20, and joins created automatically by the
     * ORM have a value of 10.
     * @param array $parameters Any additional parameters if the join is a parameterised subquery
     * @return $this
     */
    public function leftJoin($table, $onClause, $alias = null, $order = 20, $parameters = [])
    {
        if ($table) {
            $this->query->addLeftJoin($table, $onClause, $alias, $order, $parameters);
        }
        return $this;
    }

    /**
     * Add a RIGHT JOIN clause to this query.
     *
     * @param string $table The unquoted table to join to.
     * @param string $onClause The filter for the join (escaped SQL statement).
     * @param string $alias An optional alias name (unquoted)
     * @param int $order A numerical index to control the order that joins are added to the query; lower order values
     * will cause the query to appear first. The default is 20, and joins created automatically by the
     * ORM have a value of 10.
     * @param array $parameters Any additional parameters if the join is a parameterised subquery
     * @return $this
     */
    public function rightJoin($table, $onClause, $alias = null, $order = 20, $parameters = [])
    {
        if ($table) {
            $this->query->addRightJoin($table, $onClause, $alias, $order, $parameters);
        }
        return $this;
    }

    /**
     * Prefix of all joined table aliases. E.g. ->filter('Banner.Image.Title)'
     * Will join the Banner, and then Image relations
     * `$relationPrefx` will be `banner_image_`
     * Each table in the Image chain will be suffixed to this prefix. E.g.
     * `banner_image_File` and `banner_image_Image`
     *
     * This will be null if no relation is joined.
     * E.g. `->filter('Title')`
     *
     * @param string|array $relation Relation in '.' delimited string, or array of parts
     * @return string Table prefix
     */
    public static function applyRelationPrefix($relation)
    {
        if (!$relation) {
            return null;
        }
        if (is_string($relation)) {
            $relation = explode(".", $relation ?? '');
        }
        return strtolower(implode('_', $relation)) . '_';
    }

    /**
     * Traverse the relationship fields, and add the table
     * mappings to the query object state. This has to be called
     * in any overloaded {@link SearchFilter->apply()} methods manually.
     *
     * Note, that in order to filter against the joined relation user code must
     * use {@see tablePrefix()} to get the table alias used for this relation.
     *
     * @param string|array $relation The array/dot-syntax relation to follow
     * @param bool $linearOnly Set to true to restrict to linear relations only. Set this
     * if this relation will be used for sorting, and should not include duplicate rows.
     * @return string The model class of the related item
     */
    public function applyRelation($relation, $linearOnly = false)
    {
        // NO-OP
        if (!$relation) {
            return $this->dataClass;
        }

        if (is_string($relation)) {
            $relation = explode(".", $relation ?? '');
        }

        $modelClass = $this->dataClass;

        $schema = DataObject::getSchema();
        $currentRelation = [];
        foreach ($relation as $rel) {
            // Get prefix for join for this table (and parent to join on)
            $parentPrefix = $this->applyRelationPrefix($currentRelation);
            $currentRelation[] = $rel;
            $tablePrefix = $this->applyRelationPrefix($currentRelation);

            // Check has_one
            if ($component = $schema->hasOneComponent($modelClass, $rel)) {
                // Join via has_one
                $this->joinHasOneRelation($modelClass, $rel, $component, $parentPrefix, $tablePrefix);
                $modelClass = $component;
                continue;
            }

            // Check has_many
            if ($component = $schema->hasManyComponent($modelClass, $rel)) {
                // Fail on non-linear relations
                if ($linearOnly) {
                    throw new InvalidArgumentException("$rel is not a linear relation on model $modelClass");
                }
                // Join via has_many
                $this->joinHasManyRelation($modelClass, $rel, $component, $parentPrefix, $tablePrefix, 'has_many');
                $modelClass = $component;
                continue;
            }

            // check belongs_to (like has_many but linear safe)
            if ($component = $schema->belongsToComponent($modelClass, $rel)) {
                // Piggy back off has_many logic
                $this->joinHasManyRelation($modelClass, $rel, $component, $parentPrefix, $tablePrefix, 'belongs_to');
                $modelClass = $component;
                continue;
            }

            // Check many_many
            if ($component = $schema->manyManyComponent($modelClass, $rel)) {
                // Fail on non-linear relations
                if ($linearOnly) {
                    throw new InvalidArgumentException("$rel is not a linear relation on model $modelClass");
                }
                $this->joinManyManyRelationship(
                    $component['relationClass'],
                    $component['parentClass'],
                    $component['childClass'],
                    $component['parentField'],
                    $component['childField'],
                    $component['join'],
                    $parentPrefix,
                    $tablePrefix
                );
                $modelClass = $component['childClass'];
                continue;
            }

            // no relation
            throw new InvalidArgumentException("$rel is not a relation on model $modelClass");
        }

        return $modelClass;
    }

    /**
     * Join the given has_many relation to this query.
     * Also works with belongs_to
     *
     * @param string $localClass Name of class that has the has_many to the joined class
     * @param string $localField Name of the has_many relationship to join
     * @param string $foreignClass Class to join
     * @param string $localPrefix Table prefix for parent class
     * @param string $foreignPrefix Table prefix to use
     * @param string $type 'has_many' or 'belongs_to'
     */
    protected function joinHasManyRelation(
        $localClass,
        $localField,
        $foreignClass,
        $localPrefix = null,
        $foreignPrefix = null,
        $type = 'has_many'
    ) {
        if (!$foreignClass || $foreignClass === DataObject::class) {
            throw new InvalidArgumentException("Could not find a has_many relationship {$localField} on {$localClass}");
        }
        $schema = DataObject::getSchema();

        // Skip if already joined
        // Note: don't just check base class, since we need to join on the table with the actual relation key
        $foreignTable = $schema->tableName($foreignClass);
        $foreignTableAliased = $foreignPrefix . $foreignTable;
        if ($this->query->isJoinedTo($foreignTableAliased)) {
            return;
        }

        // Join table with associated has_one
        $foreignKey = $schema->getRemoteJoinField($localClass, $localField, $type, $polymorphic);
        $localIDColumn = $schema->sqlColumnForField($localClass, 'ID', $localPrefix);
        if ($polymorphic) {
            $foreignKeyIDColumn = $schema->sqlColumnForField($foreignClass, "{$foreignKey}ID", $foreignPrefix);
            $foreignKeyClassColumn = $schema->sqlColumnForField($foreignClass, "{$foreignKey}Class", $foreignPrefix);
            $localClassColumn = $schema->sqlColumnForField($localClass, 'ClassName', $localPrefix);
            $joinExpression =
                "{$foreignKeyIDColumn} = {$localIDColumn} AND {$foreignKeyClassColumn} = {$localClassColumn}";

            // Add relation key if the has_many points to a has_one that could handle multiple reciprocal has_many relations
            if ($type === 'has_many') {
                $details = $schema->getHasManyComponentDetails($localClass, $localField);
                if ($details['needsRelation']) {
                    $foreignKeyRelationColumn = $schema->sqlColumnForField($foreignClass, "{$foreignKey}Relation", $foreignPrefix);
                    $joinExpression .= " AND {$foreignKeyRelationColumn} = {$localField}";
                }
            }
        } else {
            $foreignKeyIDColumn = $schema->sqlColumnForField($foreignClass, $foreignKey, $foreignPrefix);
            $joinExpression = "{$foreignKeyIDColumn} = {$localIDColumn}";
        }
        $this->query->addLeftJoin(
            $this->getJoinTableName($foreignClass, $foreignTable),
            $joinExpression,
            $foreignTableAliased
        );

        // Add join clause to the component's ancestry classes so that the search filter could search on
        // its ancestor fields.
        $ancestry = ClassInfo::ancestry($foreignClass, true);
        $ancestry = array_reverse($ancestry ?? []);
        foreach ($ancestry as $ancestor) {
            $ancestorTable = $schema->tableName($ancestor);
            if ($ancestorTable !== $foreignTable) {
                $ancestorTableAliased = $foreignPrefix . $ancestorTable;
                $this->query->addLeftJoin(
                    $this->getJoinTableName($ancestor, $ancestorTable),
                    "\"{$foreignTableAliased}\".\"ID\" = \"{$ancestorTableAliased}\".\"ID\"",
                    $ancestorTableAliased
                );
            }
        }
    }

    /**
     * Join the given class to this query with the given key
     *
     * @param string $localClass Name of class that has the has_one to the joined class
     * @param string $localField Name of the has_one relationship to joi
     * @param string $foreignClass Class to join
     * @param string $localPrefix Table prefix to use for local class
     * @param string $foreignPrefix Table prefix to use for joined table
     */
    protected function joinHasOneRelation(
        $localClass,
        $localField,
        $foreignClass,
        $localPrefix = null,
        $foreignPrefix = null
    ) {
        if (!$foreignClass) {
            throw new InvalidArgumentException("Could not find a has_one relationship {$localField} on {$localClass}");
        }

        if ($foreignClass === DataObject::class) {
            throw new InvalidArgumentException(
                "Could not join polymorphic has_one relationship {$localField} on {$localClass}"
            );
        }
        $schema = DataObject::getSchema();

        // Skip if already joined
        $foreignBaseClass = $schema->baseDataClass($foreignClass);
        $foreignBaseTable = $schema->tableName($foreignBaseClass);
        if ($this->query->isJoinedTo($foreignPrefix . $foreignBaseTable)) {
            return;
        }

        // Join base table
        $foreignIDColumn = $schema->sqlColumnForField($foreignBaseClass, 'ID', $foreignPrefix);
        $localColumn = $schema->sqlColumnForField($localClass, "{$localField}ID", $localPrefix);
        $this->query->addLeftJoin(
            $this->getJoinTableName($foreignClass, $foreignBaseTable),
            "{$foreignIDColumn} = {$localColumn}",
            $foreignPrefix . $foreignBaseTable
        );

        // Add join clause to the component's ancestry classes so that the search filter could search on
        // its ancestor fields.
        $ancestry = ClassInfo::ancestry($foreignClass, true);
        if (!empty($ancestry)) {
            $ancestry = array_reverse($ancestry ?? []);
            foreach ($ancestry as $ancestor) {
                $ancestorTable = $schema->tableName($ancestor);
                if ($ancestorTable !== $foreignBaseTable) {
                    $ancestorTableAliased = $foreignPrefix . $ancestorTable;
                    $this->query->addLeftJoin(
                        $this->getJoinTableName($ancestor, $ancestorTable),
                        "{$foreignIDColumn} = \"{$ancestorTableAliased}\".\"ID\"",
                        $ancestorTableAliased
                    );
                }
            }
        }
    }

    /**
     * Join table via many_many relationship
     *
     * @param string $relationClass
     * @param string $parentClass
     * @param string $componentClass
     * @param string $parentField
     * @param string $componentField
     * @param string $relationClassOrTable Name of relation table
     * @param string $parentPrefix Table prefix for parent class
     * @param string $componentPrefix Table prefix to use for both joined and mapping table
     */
    protected function joinManyManyRelationship(
        $relationClass,
        $parentClass,
        $componentClass,
        $parentField,
        $componentField,
        $relationClassOrTable,
        $parentPrefix = null,
        $componentPrefix = null
    ) {
        $schema = DataObject::getSchema();

        if (class_exists($relationClassOrTable ?? '')) {
            // class is provided
            $relationTable = $schema->tableName($relationClassOrTable);
            $relationTableUpdated = $this->getJoinTableName($relationClassOrTable, $relationTable);
        } else {
            // table is provided
            $relationTable = $relationClassOrTable;
            $relationTableUpdated = $relationClassOrTable;
        }

        // Check if already joined to component alias (skip join table for the check)
        $componentBaseClass = $schema->baseDataClass($componentClass);
        $componentBaseTable = $schema->tableName($componentBaseClass);
        $componentAliasedTable = $componentPrefix . $componentBaseTable;
        if ($this->query->isJoinedTo($componentAliasedTable)) {
            return;
        }

        // Join parent class to join table
        $relationAliasedTable = $componentPrefix . $relationTable;
        $parentIDColumn = $schema->sqlColumnForField($parentClass, 'ID', $parentPrefix);
        $this->query->addLeftJoin(
            $relationTableUpdated,
            "\"{$relationAliasedTable}\".\"{$parentField}\" = {$parentIDColumn}",
            $relationAliasedTable
        );

        // Join on base table of component class
        $componentIDColumn = $schema->sqlColumnForField($componentBaseClass, 'ID', $componentPrefix);
            $this->query->addLeftJoin(
                $this->getJoinTableName($componentBaseClass, $componentBaseTable),
                "\"{$relationAliasedTable}\".\"{$componentField}\" = {$componentIDColumn}",
                $componentAliasedTable
            );

        // Add join clause to the component's ancestry classes so that the search filter could search on
        // its ancestor fields.
        $ancestry = ClassInfo::ancestry($componentClass, true);
        $ancestry = array_reverse($ancestry ?? []);
        foreach ($ancestry as $ancestor) {
            $ancestorTable = $schema->tableName($ancestor);
            if ($ancestorTable !== $componentBaseTable) {
                $ancestorTableAliased = $componentPrefix . $ancestorTable;
                $this->query->addLeftJoin(
                    $this->getJoinTableName($ancestor, $ancestorTable),
                    "{$componentIDColumn} = \"{$ancestorTableAliased}\".\"ID\"",
                    $ancestorTableAliased
                );
            }
        }
    }

    /**
     * Removes the result of query from this query.
     *
     * @param DataQuery $subtractQuery
     * @param string $field
     * @return $this
     */
    public function subtract(DataQuery $subtractQuery, $field = 'ID')
    {
        $fieldExpression = $subtractQuery->expressionForField($field);
        $subSelect = $subtractQuery->getFinalisedQuery();
        $subSelect->setSelect([]);
        $subSelect->selectField($fieldExpression, $field);
        $subSelect->setOrderBy(null);
        $subSelectSQL = $subSelect->sql($subSelectParameters);
        $this->where([$this->expressionForField($field) . " NOT IN ($subSelectSQL)" => $subSelectParameters]);

        return $this;
    }

    /**
     * Select the only given fields from the given table.
     *
     * @param string $table Unquoted table name (will be escaped automatically)
     * @param array $fields Database column names (will be escaped automatically)
     * @return $this
     */
    public function selectFromTable($table, $fields)
    {
        $fieldExpressions = array_map(function ($item) use ($table) {
            return Convert::symbol2sql("{$table}.{$item}");
        }, $fields ?? []);

        $this->query->setSelect($fieldExpressions);

        return $this;
    }

    /**
     * Add the given fields from the given table to the select statement.
     *
     * @param string $table Unquoted table name (will be escaped automatically)
     * @param array $fields Database column names (will be escaped automatically)
     * @return $this
     */
    public function addSelectFromTable($table, $fields)
    {
        $fieldExpressions = array_map(function ($item) use ($table) {
            return Convert::symbol2sql("{$table}.{$item}");
        }, $fields ?? []);

        $this->query->addSelect($fieldExpressions);

        return $this;
    }

    /**
     * Query the given field column from the database and return as an array.
     * querying DB columns of related tables is supported but you need to make sure that the related table
     * is already available in join
     *
     * @see DataList::applyRelation()
     *
     * example use:
     *
     * <code>
     *  column("MyTable"."Title")
     *
     *  or
     *
     *  $columnName = null;
     *  Category::get()
     *    ->applyRelation('Products.Title', $columnName)
     *    ->column($columnName);
     * </code>
     *
     * @param string $field See {@link expressionForField()}.
     * @return array List of column values for the specified column
     * @throws InvalidArgumentException
     */
    public function column($field = 'ID')
    {
        $fieldExpression = $this->expressionForField($field);
        $query = $this->getFinalisedQuery([$field]);
        $originalSelect = $query->getSelect();
        $query->setSelect([]);

        // field wasn't recognised as a valid field from the table class hierarchy
        // check if the field is in format "<table_name>"."<column_name>"
        // if that's the case we may want to query related table
        if (!$fieldExpression) {
            if (!$this->validateColumnField($field, $query)) {
                throw new InvalidArgumentException('Invalid column name ' . $field);
            }

            $fieldExpression = $field;
            $field = null;
        }

        $query->selectField($fieldExpression, $field);
        $this->ensureSelectContainsOrderbyColumns($query, $originalSelect);

        return $query->execute()->column($field);
    }

    /**
     * @param string $field Select statement identifier, either the unquoted column name,
     * the full composite SQL statement, or the alias set through {@link SQLSelect->selectField()}.
     * @return string The expression used to query this field via this DataQuery
     */
    protected function expressionForField($field)
    {
        // Prepare query object for selecting this field
        $query = $this->getFinalisedQuery([$field]);

        // Allow query to define the expression for this field
        $expression = $query->expressionForField($field);
        if (!empty($expression)) {
            return $expression;
        }

        // Special case for ID, if not provided
        if ($field === 'ID') {
            return DataObject::getSchema()->sqlColumnForField($this->dataClass, 'ID');
        }
        return null;
    }

    /**
     * Select the given field expressions.
     *
     * @param string $fieldExpression String The field to select (escaped SQL statement)
     * @param string $alias String The alias of that field (escaped SQL statement)
     */
    public function selectField($fieldExpression, $alias = null)
    {
        $this->query->selectField($fieldExpression, $alias);
    }

    //// QUERY PARAMS

    /**
     * An arbitrary store of query parameters that can be used by decorators.
     */
    private $queryParams;

    /**
     * Set an arbitrary query parameter, that can be used by decorators to add additional meta-data to the query.
     * It's expected that the $key will be namespaced, e.g, 'Versioned.stage' instead of just 'stage'.
     *
     * @param string $key
     * @param string|array $value
     * @return $this
     */
    public function setQueryParam($key, $value)
    {
        $this->queryParams[$key] = $value;
        return $this;
    }

    /**
     * Set an arbitrary query parameter, that can be used by decorators to add additional meta-data to the query.
     *
     * @param string $key
     * @return string
     */
    public function getQueryParam($key)
    {
        if (isset($this->queryParams[$key])) {
            return $this->queryParams[$key];
        }
        return null;
    }

    /**
     * Returns all query parameters
     * @return array query parameters array
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * Get query manipulators
     *
     * @return DataQueryManipulator[]
     */
    public function getDataQueryManipulators()
    {
        return $this->dataQueryManipulators;
    }

    /**
     * Assign callback to be invoked in getFinalisedQuery()
     *
     * @param DataQueryManipulator $manipulator
     * @return $this
     */
    public function pushQueryManipulator(DataQueryManipulator $manipulator)
    {
        $this->dataQueryManipulators[] = $manipulator;
        return $this;
    }

    /**
     * Get whether field statements aliased as columns are allowed when that column is already
     * being selected
     */
    public function getAllowCollidingFieldStatements(): bool
    {
        return $this->allowCollidingFieldStatements;
    }

    /**
     * Set whether field statements aliased as columns are allowed when that column is already
     * being selected
     */
    public function setAllowCollidingFieldStatements(bool $value): static
    {
        $this->allowCollidingFieldStatements = $value;
        return $this;
    }

    private function validateColumnField($field, SQLSelect $query)
    {
        // standard column - nothing to process here
        if (strpos($field ?? '', '.') === false) {
            return false;
        }

        $fieldData = explode('.', $field ?? '');
        $tablePrefix = str_replace('"', '', $fieldData[0] ?? '');

        // check if related table is available
        return $query->isJoinedTo($tablePrefix);
    }

    /**
     * Use this extension point to alter the table name
     * useful for versioning for example
     *
     * @param $class
     * @param $table
     * @return mixed
     */
    private function getJoinTableName($class, $table)
    {
        $updated = $table;
        $this->invokeWithExtensions('updateJoinTableName', $class, $table, $updated);

        return $updated;
    }
}
