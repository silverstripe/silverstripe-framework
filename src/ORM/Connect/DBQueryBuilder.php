<?php

namespace SilverStripe\ORM\Connect;

use InvalidArgumentException;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ListDecorator;
use SilverStripe\ORM\Map;
use SilverStripe\ORM\Queries\SQLExpression;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\Queries\SQLDelete;
use SilverStripe\ORM\Queries\SQLInsert;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\ORM\Queries\SQLConditionalExpression;

/**
 * Builds a SQL query string from a SQLExpression object
 */
class DBQueryBuilder
{
    use Configurable;

    /**
     * If true, a comment is added to each query indicating where that query's execution originated.
     */
    private static bool $trace_query_origin = false;

    /**
     * Determines the line separator to use.
     *
     * @return string Non-empty whitespace character
     */
    public function getSeparator()
    {
        return "\n ";
    }

    /**
     * Builds a sql query with the specified connection
     *
     * @param SQLExpression $query The expression object to build from
     * @param array $parameters Out parameter for the resulting query parameters
     * @return string The resulting SQL as a string
     */
    public function buildSQL(SQLExpression $query, &$parameters)
    {
        $sql = null;
        $parameters = [];

        // Ignore null queries
        if ($query->isEmpty()) {
            return null;
        }

        if ($query instanceof SQLSelect) {
            $sql = $this->buildSelectQuery($query, $parameters);
        } elseif ($query instanceof SQLDelete) {
            $sql = $this->buildDeleteQuery($query, $parameters);
        } elseif ($query instanceof SQLInsert) {
            $sql = $this->buildInsertQuery($query, $parameters);
        } elseif ($query instanceof SQLUpdate) {
            $sql = $this->buildUpdateQuery($query, $parameters);
        } else {
            throw new InvalidArgumentException(
                "Not implemented: query generation for type " . get_class($query)
            );
        }

        if ($this->shouldBuildTraceComment()) {
            $sql = $this->buildTraceComment() . $sql;
        }

        return $sql;
    }

    private function shouldBuildTraceComment(): bool
    {
        if (Environment::hasEnv('SS_TRACE_DB_QUERY_ORIGIN')) {
            return (bool) Environment::getEnv('SS_TRACE_DB_QUERY_ORIGIN');
        }
        return static::config()->get('trace_query_origin');
    }

    /**
     * Builds an SQL comment indicating where the query was executed from.
     */
    protected function buildTraceComment(): string
    {
        $comment = '/* ';

        // Skip items in the stack trace that originate from these classes or their subclasses,
        // we want to know what called these instead
        $baseClasses = [
            DBQueryBuilder::class,
            DataQuery::class,
            SQLExpression::class,
            DB::class,
            Database::class,
            DBConnector::class,
            DBSchemaManager::class,
            TransactionManager::class,
            ListDecorator::class,
            Map::class,
        ];
        // Skip items in the stack trace that originate from these methods,
        // we want to know what called these instead
        $ignoreMethods = [
            DataList::class => [
                // these are used in almost all DataList query executions
                'executeQuery',
                'getFinalisedQuery',
                'getIterator',
                // these call a method on DataList (e.g. $this->toNestedArray())
                'debug',
                'setByIDList',
            ],
            DataObject::class => [
                'get_one',
                'get_by_id',
            ]
        ];

        $line = null;
        $file = null;
        $class = null;
        $function = null;

        // Don't include arguments in the trace (since we don't need them), and only go back 15 levels.
        // Anything further than that and we've probably over-abstracted things.
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
        foreach ($trace as $i => $item) {
            // We need to be able to look ahead one item in the trace, because the class/function values
            // are talking about what is being *called* on this line, not the function this line lives in.
            if (!isset($trace[$i+1])) {
                return '/* Could not identify source of query */' . $this->getSeparator();
            }
            $caller = [
                'file' => $item['file'] ?? null,
                'line' => $item['line'] ?? null,
                'class' => $trace[$i + 1]['class'] ?? null,
                'function' => $trace[$i + 1]['function'] ?? null,
            ];

            if ($caller['class'] !== null) {
                // Don't report internal ORM operations for any of these classes
                foreach ($baseClasses as $baseClass) {
                    if (is_a($caller['class'], $baseClass, true)) {
                        // skip for both loops
                        continue 2;
                    }
                }
                if ($caller['function'] !== null) {
                    // Don't report internal ORM operations for any of these methods
                    foreach ($ignoreMethods as $class => $methodsToIgnore) {
                        if (is_a($caller['class'], $class, true) && in_array($caller['function'], $methodsToIgnore)) {
                            // skip for both loops
                            continue 2;
                        }
                    }
                    // Don't report internal ORM operations inside DataList which themselves directly call methods on DataQuery
                    if ($caller['class'] === DataList::class && is_a($item['class'] ?? '', DataQuery::class, true)) {
                        continue;
                    }
                    // Don't report internal ORM operations inside DataList for eagerloading or for any methods that iterate over the list itself
                    if ($caller['class'] === DataList::class &&
                        (str_starts_with($caller['function'], 'fetchEagerLoad')
                        || is_a($item['class'] ?? '', DataList::class, true) && in_array($item['function'], $ignoreMethods[DataList::class]))
                    ) {
                        continue;
                    }
                }
            }

            // Get the relevant trace information if it's available
            $file = $caller['file'];
            $line = $caller['line'];
            $class = $caller['class'];
            $function = $caller['function'];
            break;
        }

        // Indicate where the query was executed from, if we have that information.
        if ($line && $file) {
            $comment .= "Query executed from $file line $line";
        } elseif ($class && $function) {
            $comment .= "Query executed from {$class}::{$function}()";
        } else {
            $comment .= 'Could not identify source of query';
        }

        $comment .= ' */' . $this->getSeparator();
        return $comment;
    }

    /**
     * Builds a query from a SQLSelect expression
     *
     * @param SQLSelect $query The expression object to build from
     * @param array $parameters Out parameter for the resulting query parameters
     * @return string Completed SQL string
     */
    protected function buildSelectQuery(SQLSelect $query, array &$parameters)
    {
        $needsParenthisis = count($query->getUnions()) > 0;
        $nl = $this->getSeparator();
        $sql = '';
        if ($needsParenthisis) {
            $sql .= "({$nl}";
        }
        $sql .= $this->buildWithFragment($query, $parameters);
        $sql .= $this->buildSelectFragment($query, $parameters);
        $sql .= $this->buildFromFragment($query, $parameters);
        $sql .= $this->buildWhereFragment($query, $parameters);
        $sql .= $this->buildGroupByFragment($query, $parameters);
        $sql .= $this->buildHavingFragment($query, $parameters);
        $sql .= $this->buildOrderByFragment($query, $parameters);
        $sql .= $this->buildLimitFragment($query, $parameters);
        if ($needsParenthisis) {
            $sql .= "{$nl})";
        }
        $sql .= $this->buildUnionFragment($query, $parameters);
        return $sql;
    }

    /**
     * Builds a query from a SQLDelete expression
     *
     * @param SQLDelete $query The expression object to build from
     * @param array $parameters Out parameter for the resulting query parameters
     * @return string Completed SQL string
     */
    protected function buildDeleteQuery(SQLDelete $query, array &$parameters)
    {
        $sql  = $this->buildDeleteFragment($query, $parameters);
        $sql .= $this->buildFromFragment($query, $parameters);
        $sql .= $this->buildWhereFragment($query, $parameters);
        return $sql;
    }

    /**
     * Builds a query from a SQLInsert expression
     *
     * @param SQLInsert $query The expression object to build from
     * @param array $parameters Out parameter for the resulting query parameters
     * @return string Completed SQL string
     */
    protected function buildInsertQuery(SQLInsert $query, array &$parameters)
    {
        $nl = $this->getSeparator();
        $into = $query->getInto();

        // Column identifiers
        $columns = $query->getColumns();
        $sql = "INSERT INTO {$into}{$nl}(" . implode(', ', $columns) . ")";

        // Values
        $sql .= "{$nl}VALUES";

        // Build all rows
        $rowParts = [];
        foreach ($query->getRows() as $row) {
            // Build all columns in this row
            $assignments = $row->getAssignments();
            // Join SET components together, considering parameters
            $parts = [];
            foreach ($columns as $column) {
                // Check if this column has a value for this row
                if (isset($assignments[$column])) {
                    // Assignment is a single item array, expand with a loop here
                    foreach ($assignments[$column] as $assignmentSQL => $assignmentParameters) {
                        $parts[] = $assignmentSQL;
                        $parameters = array_merge($parameters, $assignmentParameters);
                        break;
                    }
                } else {
                    // This row is missing a value for a column used by another row
                    $parts[] = '?';
                    $parameters[] = null;
                }
            }
            $rowParts[] = '(' . implode(', ', $parts) . ')';
        }
        $sql .= $nl . implode(",$nl", $rowParts);

        return $sql;
    }

    /**
     * Builds a query from a SQLUpdate expression
     *
     * @param SQLUpdate $query The expression object to build from
     * @param array $parameters Out parameter for the resulting query parameters
     * @return string Completed SQL string
     */
    protected function buildUpdateQuery(SQLUpdate $query, array &$parameters)
    {
        $sql  = $this->buildUpdateFragment($query, $parameters);
        $sql .= $this->buildWhereFragment($query, $parameters);
        return $sql;
    }

    /**
     * Returns the WITH clauses ready for inserting into a query.
     */
    protected function buildWithFragment(SQLSelect $query, array &$parameters): string
    {
        $with = $query->getWith();
        if (empty($with)) {
            return '';
        }

        $nl = $this->getSeparator();
        $clauses = [];

        foreach ($with as $name => $bits) {
            $clause = $bits['recursive'] ? 'RECURSIVE ' : '';
            $clause .= Convert::symbol2sql($name);

            if (!empty($bits['cte_fields'])) {
                $cteFields = $bits['cte_fields'];
                // Ensure all cte fields are escaped correctly
                array_walk($cteFields, function (&$colName) {
                    $colName = preg_match('/^".*"$/', $colName) ? $colName : Convert::symbol2sql($colName);
                });
                $clause .= ' (' . implode(', ', $cteFields) . ')';
            }

            $clause .= " AS ({$nl}";
            $clause .= $this->buildSelectQuery($bits['query'], $parameters);
            $clause .= "{$nl})";
            $clauses[] = $clause;
        }

        return 'WITH ' . implode(",{$nl}", $clauses) . $nl;
    }

    /**
     * Returns the SELECT clauses ready for inserting into a query.
     *
     * @param SQLSelect $query The expression object to build from
     * @param array $parameters Out parameter for the resulting query parameters
     * @return string Completed select part of statement
     */
    protected function buildSelectFragment(SQLSelect $query, array &$parameters)
    {
        $distinct = $query->getDistinct();
        $select = $query->getSelect();
        $clauses = [];

        foreach ($select as $alias => $field) {
            // Don't include redundant aliases.
            $fieldAlias = "\"{$alias}\"";
            if ($alias === $field || substr($field ?? '', -strlen($fieldAlias ?? '')) === $fieldAlias) {
                $clauses[] = $field;
            } else {
                $clauses[] = "$field AS $fieldAlias";
            }
        }

        $text = 'SELECT ';
        if ($distinct) {
            $text .= 'DISTINCT ';
        }
        return $text . implode(', ', $clauses);
    }

    /**
     * Return the DELETE clause ready for inserting into a query.
     *
     * @param SQLDelete $query The expression object to build from
     * @param array $parameters Out parameter for the resulting query parameters
     * @return string Completed delete part of statement
     */
    public function buildDeleteFragment(SQLDelete $query, array &$parameters)
    {
        $text = 'DELETE';

        // If doing a multiple table delete then list the target deletion tables here
        // Note that some schemas don't support multiple table deletion
        $delete = $query->getDelete();
        if (!empty($delete)) {
            $text .= ' ' . implode(', ', $delete);
        }
        return $text;
    }

    /**
     * Return the UPDATE clause ready for inserting into a query.
     *
     * @param SQLUpdate $query The expression object to build from
     * @param array $parameters Out parameter for the resulting query parameters
     * @return string Completed from part of statement
     */
    public function buildUpdateFragment(SQLUpdate $query, array &$parameters)
    {
        $table = $query->getTable();
        $text = "UPDATE $table";

        // Join SET components together, considering parameters
        $parts = [];
        foreach ($query->getAssignments() as $column => $assignment) {
            // Assignment is a single item array, expand with a loop here
            foreach ($assignment as $assignmentSQL => $assignmentParameters) {
                $parts[] = "$column = $assignmentSQL";
                $parameters = array_merge($parameters, $assignmentParameters);
                break;
            }
        }
        $nl = $this->getSeparator();
        $text .= "{$nl}SET " . implode(', ', $parts);
        return $text;
    }

    /**
     * Return the FROM clause ready for inserting into a query.
     *
     * @param SQLConditionalExpression $query The expression object to build from
     * @param array $parameters Out parameter for the resulting query parameters
     * @return string Completed from part of statement
     */
    public function buildFromFragment(SQLConditionalExpression $query, array &$parameters)
    {
        $from = $query->getJoins($joinParameters);
        $tables = [];
        $joins = [];

        // E.g. a naive "Select 1" statement is valid SQL
        if (empty($from)) {
            return '';
        }

        foreach ($from as $joinOrTable) {
            if (preg_match(SQLConditionalExpression::getJoinRegex(), $joinOrTable)) {
                $joins[] = $joinOrTable;
            } else {
                $tables[] = $joinOrTable;
            }
        }

        $parameters = array_merge($parameters, $joinParameters);
        $nl = $this->getSeparator();
        return  "{$nl}FROM " . implode(', ', $tables) . ' ' . implode(' ', $joins);
    }

    /**
     * Returns the WHERE clauses ready for inserting into a query.
     *
     * @param SQLConditionalExpression $query The expression object to build from
     * @param array $parameters Out parameter for the resulting query parameters
     * @return string Completed where condition
     */
    public function buildWhereFragment(SQLConditionalExpression $query, array &$parameters)
    {
        // Get parameterised elements
        $where = $query->getWhereParameterised($whereParameters);
        if (empty($where)) {
            return '';
        }

        // Join conditions
        $connective = $query->getConnective();
        $parameters = array_merge($parameters, $whereParameters);
        $nl = $this->getSeparator();
        return "{$nl}WHERE (" . implode("){$nl}{$connective} (", $where) . ")";
    }

    /**
     * Return the UNION clause(s) ready for inserting into a query.
     */
    protected function buildUnionFragment(SQLSelect $query, array &$parameters): string
    {
        $unions = $query->getUnions();
        if (empty($unions)) {
            return '';
        }

        $nl = $this->getSeparator();
        $clauses = [];

        foreach ($unions as $union) {
            $unionQuery = $union['query'];
            $unionType = $union['type'];

            $clause = "{$nl}UNION";

            if ($unionType) {
                $clause .= " $unionType";
            }

            $clause .= "$nl($nl" . $this->buildSelectQuery($unionQuery, $parameters) . "$nl)";

            $clauses[] = $clause;
        }

        return implode('', $clauses);
    }

    /**
     * Returns the ORDER BY clauses ready for inserting into a query.
     *
     * @param SQLSelect $query The expression object to build from
     * @param array $parameters Out parameter for the resulting query parameters
     * @return string Completed order by part of statement
     */
    public function buildOrderByFragment(SQLSelect $query, array &$parameters)
    {
        $orderBy = $query->getOrderBy();
        if (empty($orderBy)) {
            return '';
        }

        // Build orders, each with direction considered
        $statements = [];
        foreach ($orderBy as $clause => $dir) {
            $statements[] = trim("$clause $dir");
        }

        $nl = $this->getSeparator();
        return "{$nl}ORDER BY " . implode(', ', $statements);
    }

    /**
     * Returns the GROUP BY clauses ready for inserting into a query.
     *
     * @param SQLSelect $query The expression object to build from
     * @param array $parameters Out parameter for the resulting query parameters
     * @return string Completed group part of statement
     */
    public function buildGroupByFragment(SQLSelect $query, array &$parameters)
    {
        $groupBy = $query->getGroupBy();
        if (empty($groupBy)) {
            return '';
        }

        $nl = $this->getSeparator();
        return "{$nl}GROUP BY " . implode(', ', $groupBy);
    }

    /**
     * Returns the HAVING clauses ready for inserting into a query.
     *
     * @param SQLSelect $query The expression object to build from
     * @param array $parameters Out parameter for the resulting query parameters
     * @return string
     */
    public function buildHavingFragment(SQLSelect $query, array &$parameters)
    {
        $having = $query->getHavingParameterised($havingParameters);
        if (empty($having)) {
            return '';
        }

        // Generate having, considering parameters present
        $connective = $query->getConnective();
        $parameters = array_merge($parameters, $havingParameters);
        $nl = $this->getSeparator();
        return "{$nl}HAVING (" . implode("){$nl}{$connective} (", $having) . ")";
    }

    /**
     * Return the LIMIT clause ready for inserting into a query.
     *
     * @param SQLSelect $query The expression object to build from
     * @param array $parameters Out parameter for the resulting query parameters
     * @return string The finalised limit SQL fragment
     */
    public function buildLimitFragment(SQLSelect $query, array &$parameters)
    {
        $nl = $this->getSeparator();

        // Ensure limit is given
        $limit = $query->getLimit();
        if (empty($limit)) {
            return '';
        }

        // For literal values return this as the limit SQL
        if (!is_array($limit)) {
            return "{$nl}LIMIT $limit";
        }

        // Assert that the array version provides the 'limit' key
        if (!isset($limit['limit']) || !is_numeric($limit['limit'])) {
            throw new InvalidArgumentException(
                'DBQueryBuilder::buildLimitSQL(): Wrong format for $limit: ' . var_export($limit, true)
            );
        }

        // Format the array limit, given an optional start key
        $clause = "{$nl}LIMIT {$limit['limit']}";
        if (isset($limit['start']) && is_numeric($limit['start']) && $limit['start'] !== 0) {
            $clause .= " OFFSET {$limit['start']}";
        }
        return $clause;
    }
}
