<?php

namespace SilverStripe\ORM\Queries;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DB;
use InvalidArgumentException;
use LogicException;

/**
 * Object representing a SQL SELECT query.
 * The various parts of the SQL query can be manipulated individually.
 */
class SQLSelect extends SQLConditionalExpression
{
    public const UNION_ALL = 'ALL';

    public const UNION_DISTINCT = 'DISTINCT';

    /**
     * An array of SELECT fields, keyed by an optional alias.
     *
     * @var array
     */
    protected $select = [];

    /**
     * An array of GROUP BY clauses.
     *
     * @var array
     */
    protected $groupby = [];

    /**
     * An array of having clauses.
     * Each item in this array will be in the form of a single-length array
     * in the format ['predicate' => [$parameters]]
     *
     * @var array
     */
    protected $having = [];

    /**
     * An array of subqueries to union with this one.
     */
    protected array $union = [];

    /**
     * An array of WITH clauses.
     * This array is indexed with the name for the temporary table generated for the WITH clause,
     * and contains data in the following format:
     * [
     *   'cte_fields' => string[],
     *   'query' => SQLSelect|null,
     *   'recursive' => boolean,
     * ]
     */
    protected array $with = [];

    /**
     * If this is true DISTINCT will be added to the SQL.
     *
     * @var bool
     */
    protected $distinct = false;

    /**
     * An array of ORDER BY clauses, functions. Stores as an associative
     * array of column / function to direction.
     *
     * May be used on SELECT or single table DELETE queries in some adapters
     *
     * @var array
     */
    protected $orderby = [];

    /**
     * An array containing limit and offset keys for LIMIT clause.
     *
     * May be used on SELECT or single table DELETE queries in some adapters
     *
     * @var array
     */
    protected $limit = [];

    /**
     * Construct a new SQLSelect.
     *
     * @param array|string $select An array of SELECT fields.
     * @param array|string $from An array of FROM clauses. The first one should be just the table name.
     * Each should be ANSI quoted.
     * @param array $where An array of WHERE clauses.
     * @param array $orderby An array ORDER BY clause.
     * @param array $groupby An array of GROUP BY clauses.
     * @param array $having An array of HAVING clauses.
     * @param array|string $limit A LIMIT clause or array with limit and offset keys
     * @return static
     */
    public static function create(
        $select = "*",
        $from = [],
        $where = [],
        $orderby = [],
        $groupby = [],
        $having = [],
        $limit = []
    ) {
        return Injector::inst()->createWithArgs(__CLASS__, func_get_args());
    }

    /**
     * Construct a new SQLSelect.
     *
     * @param array|string $select An array of SELECT fields.
     * @param array|string $from An array of FROM clauses. The first one should be just the table name.
     * Each should be ANSI quoted.
     * @param array $where An array of WHERE clauses.
     * @param array $orderby An array ORDER BY clause.
     * @param array $groupby An array of GROUP BY clauses.
     * @param array $having An array of HAVING clauses.
     * @param array|string $limit A LIMIT clause or array with limit and offset keys
     */
    public function __construct(
        $select = "*",
        $from = [],
        $where = [],
        $orderby = [],
        $groupby = [],
        $having = [],
        $limit = []
    ) {

        parent::__construct($from, $where);

        $this->setSelect($select);
        $this->setOrderBy($orderby);
        $this->setGroupBy($groupby);
        $this->setHaving($having);
        $this->setLimit($limit);
    }

    /**
     * Set the list of columns to be selected by the query.
     *
     * <code>
     *  // pass fields to select as single parameter array
     *  $query->setSelect(['"Col1"', '"Col2"'])->setFrom('"MyTable"');
     *
     *  // pass fields to select as multiple parameters
     *  $query->setSelect('"Col1"', '"Col2"')->setFrom('"MyTable"');
     *
     *  // Set a list of selected fields as aliases
     *  $query->setSelect(['Name' => '"Col1"', 'Details' => '"Col2"'])->setFrom('"MyTable"');
     * </code>
     *
     * @param string|array $fields Field names should be ANSI SQL quoted. Array keys should be unquoted.
     * @return $this Self reference
     */
    public function setSelect($fields)
    {
        $this->select = [];
        if (func_num_args() > 1) {
            $fields = func_get_args();
        } elseif (!is_array($fields)) {
            $fields = [$fields];
        }
        return $this->addSelect($fields);
    }

    /**
     * Add to the list of columns to be selected by the query.
     *
     * @see setSelect for example usage
     *
     * @param string|array $fields Field names should be ANSI SQL quoted. Array keys should be unquoted.
     * @return $this Self reference
     */
    public function addSelect($fields)
    {
        if (func_num_args() > 1) {
            $fields = func_get_args();
        } elseif (!is_array($fields)) {
            $fields = [$fields];
        }
        foreach ($fields as $idx => $field) {
            if ($field === '') {
                continue;
            }
            $this->selectField($field, is_numeric($idx) ? null : $idx);
        }

        return $this;
    }

    /**
     * Select an additional field.
     *
     * @param string $field The field to select (ansi quoted SQL identifier or statement)
     * @param string|null $alias The alias of that field (unquoted SQL identifier).
     * Defaults to the unquoted column name of the $field parameter.
     * @return $this Self reference
     */
    public function selectField($field, $alias = null)
    {
        if (!$alias) {
            if (preg_match('/"([^"]+)"$/', $field ?? '', $matches)) {
                $alias = $matches[1];
            } else {
                $alias = $field;
            }
        }
        $this->select[$alias] = $field;
        return $this;
    }

    /**
     * Return the SQL expression for the given field alias.
     * Returns null if the given alias doesn't exist.
     * See {@link selectField()} for details on alias generation.
     *
     * @param string $field
     * @return string
     */
    public function expressionForField($field)
    {
        return isset($this->select[$field]) ? $this->select[$field] : null;
    }

    /**
     * Set distinct property.
     *
     * @param bool $value
     * @return $this Self reference
     */
    public function setDistinct($value)
    {
        $this->distinct = $value;
        return $this;
    }

    /**
     * Get the distinct property.
     *
     * @return bool
     */
    public function getDistinct()
    {
        return $this->distinct;
    }

    /**
     * Get the limit property.
     * @return array
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Pass LIMIT clause either as SQL snippet or in array format.
     * Internally, limit will always be stored as a map containing the keys 'start' and 'limit'
     *
     * @param int|string|array|null $limit If passed as a string or array, assumes SQL escaped data.
     * Only applies for positive values.
     * @param int $offset
     * @throws InvalidArgumentException
     * @return $this Self reference
     */
    public function setLimit($limit, $offset = 0)
    {
        if ((is_numeric($limit) && $limit < 0) || (is_numeric($offset) && $offset < 0)) {
            throw new InvalidArgumentException("SQLSelect::setLimit() only takes positive values");
        }

        if (is_numeric($limit)) {
            $this->limit = [
                'start' => (int)$offset,
                'limit' => (int)$limit,
            ];
        } elseif ($limit && is_string($limit)) {
            if (strpos($limit ?? '', ',') !== false) {
                list($start, $innerLimit) = explode(',', $limit ?? '', 2);
            } else {
                list($innerLimit, $start) = explode(' OFFSET ', strtoupper($limit ?? ''), 2);
            }

            $this->limit = [
                'start' => (int)$start,
                'limit' => (int)$innerLimit,
            ];
        } elseif ($limit === null && $offset) {
            $this->limit = [
                'start' => (int)$offset,
                'limit' => $limit
            ];
        } else {
            $this->limit = $limit;
        }

        return $this;
    }

    /**
     * Set ORDER BY clause either as SQL snippet or in array format.
     *
     * @example $sql->setOrderBy("Column");
     * @example $sql->setOrderBy("Column DESC");
     * @example $sql->setOrderBy("Column DESC, ColumnTwo ASC");
     * @example $sql->setOrderBy("Column", "DESC");
     * @example $sql->setOrderBy(["Column" => "ASC", "ColumnTwo" => "DESC"]);
     *
     * @param string|array $clauses Clauses to add (escaped SQL statement)
     * @param string $direction Sort direction, ASC or DESC
     *
     * @return $this Self reference
     */
    public function setOrderBy($clauses = null, $direction = null)
    {
        $this->orderby = [];
        return $this->addOrderBy($clauses, $direction);
    }

    /**
     * Add ORDER BY clause either as SQL snippet or in array format.
     *
     * @example $sql->addOrderBy("Column");
     * @example $sql->addOrderBy("Column DESC");
     * @example $sql->addOrderBy("Column DESC, ColumnTwo ASC");
     * @example $sql->addOrderBy("Column", "DESC");
     * @example $sql->addOrderBy(["Column" => "ASC", "ColumnTwo" => "DESC"]);
     *
     * @param string|array $clauses Clauses to add (escaped SQL statements)
     * @param string $direction Sort direction, ASC or DESC
     * @return $this Self reference
     */
    public function addOrderBy($clauses = null, $direction = null)
    {
        if (empty($clauses)) {
            return $this;
        }

        if (is_string($clauses)) {
            if (strpos($clauses ?? '', "(") !== false) {
                $sort = preg_split("/,(?![^()]*+\\))/", $clauses ?? '');
            } else {
                $sort = explode(",", $clauses ?? '');
            }

            $clauses = [];

            foreach ($sort as $clause) {
                list($column, $direction) = $this->getDirectionFromString($clause, $direction);
                $clauses[$column] = $direction;
            }
        }

        if (is_array($clauses)) {
            foreach ($clauses as $key => $value) {
                if (!is_numeric($key)) {
                    $column = trim($key ?? '');
                    $columnDir = strtoupper(trim($value ?? ''));
                } else {
                    list($column, $columnDir) = $this->getDirectionFromString($value);
                }

                $this->orderby[$column] = $columnDir;
            }
        } else {
            user_error('SQLSelect::orderby() incorrect format for $orderby', E_USER_WARNING);
        }

        // If sort contains a public function call, let's move the sort clause into a
        // separate selected field.
        //
        // Some versions of MySQL choke if you have a group public function referenced
        // directly in the ORDER BY
        if ($this->orderby) {
            $i = 0;
            $orderby = [];
            foreach ($this->orderby as $clause => $dir) {
                // public function calls and multi-word columns like "CASE WHEN ..."
                if (strpos($clause ?? '', '(') !== false || strpos($clause ?? '', " ") !== false) {
                    // Move the clause to the select fragment, substituting a placeholder column in the sort fragment.
                    $clause = trim($clause ?? '');
                    do {
                        $column = "_SortColumn{$i}";
                        ++$i;
                    } while (array_key_exists('"' . $column . '"', $this->orderby ?? []));
                    $this->selectField($clause, $column);
                    $clause = '"' . $column . '"';
                }
                $orderby[$clause] = $dir;
            }
            $this->orderby = $orderby;
        }

        return $this;
    }

    /**
     * Extract the direction part of a single-column order by clause.
     *
     * @param string $value
     * @param string $defaultDirection
     * @return array A two element array: [$column, $direction]
     */
    private function getDirectionFromString($value, $defaultDirection = null)
    {
        if (preg_match('/^(.*)(asc|desc)$/i', $value ?? '', $matches)) {
            $column = trim($matches[1] ?? '');
            $direction = strtoupper($matches[2] ?? '');
        } else {
            $column = $value;
            $direction = $defaultDirection ? $defaultDirection : "ASC";
        }
        return [$column, $direction];
    }

    /**
     * Returns the current order by as array if not already. To handle legacy
     * statements which are stored as strings. Without clauses and directions,
     * convert the orderby clause to something readable.
     *
     * @return array
     */
    public function getOrderBy()
    {
        $orderby = $this->orderby;
        if (!$orderby) {
            $orderby = [];
        }

        if (!is_array($orderby)) {
            // spilt by any commas not within brackets
            $orderby = preg_split('/,(?![^()]*+\\))/', $orderby ?? '');
        }

        foreach ($orderby as $k => $v) {
            if (strpos($v ?? '', ' ') !== false) {
                unset($orderby[$k]);

                $rule = explode(' ', trim($v ?? ''));
                $clause = $rule[0];
                $dir = (isset($rule[1])) ? $rule[1] : 'ASC';

                $orderby[$clause] = $dir;
            }
        }

        return $orderby;
    }

    /**
     * Reverses the order by clause by replacing ASC or DESC references in the
     * current order by with it's corollary.
     *
     * @return $this Self reference
     */
    public function reverseOrderBy()
    {
        $order = $this->getOrderBy();
        $this->orderby = [];

        foreach ($order as $clause => $dir) {
            $dir = (strtoupper($dir ?? '') == 'DESC') ? 'ASC' : 'DESC';
            $this->addOrderBy($clause, $dir);
        }

        return $this;
    }

    /**
     * Set a GROUP BY clause.
     *
     * @param string|array $groupby Escaped SQL statement
     * @return $this Self reference
     */
    public function setGroupBy($groupby)
    {
        $this->groupby = [];
        return $this->addGroupBy($groupby);
    }

    /**
     * Add a GROUP BY clause.
     *
     * @param string|array $groupby Escaped SQL statement
     * @return $this Self reference
     */
    public function addGroupBy($groupby)
    {
        if (is_array($groupby)) {
            $this->groupby = array_merge($this->groupby, $groupby);
        } elseif (!empty($groupby)) {
            $this->groupby[] = $groupby;
        }

        return $this;
    }

    /**
     * Set a HAVING clause.
     *
     * @see SQLSelect::addWhere() for syntax examples
     *
     * @param mixed ...$having Predicate(s) to set, as escaped SQL statements or parameterised queries
     * @return $this Self reference
     */
    public function setHaving($having)
    {
        $having = func_num_args() > 1 ? func_get_args() : $having;
        $this->having = [];
        return $this->addHaving($having);
    }

    /**
     * Add a HAVING clause
     *
     * @see SQLSelect::addWhere() for syntax examples
     *
     * @param mixed ...$having Predicate(s) to set, as escaped SQL statements or parameterised queries
     * @return $this Self reference
     */
    public function addHaving($having)
    {
        $having = $this->normalisePredicates(func_get_args());

        // If the function is called with an array of items
        $this->having = array_merge($this->having, $having);

        return $this;
    }

    /**
     * Return a list of HAVING clauses used internally.
     * @return array
     */
    public function getHaving()
    {
        return $this->having;
    }

    /**
     * Return a list of HAVING clauses used internally.
     *
     * @param array $parameters Out variable for parameters required for this query
     * @return array
     */
    public function getHavingParameterised(&$parameters)
    {
        $this->splitQueryParameters($this->having, $conditions, $parameters);
        return $conditions;
    }

    /**
     * Add a select query to UNION with.
     *
     * @param string|null $type One of the UNION_ALL or UNION_DISTINCT constants - or null for a default union
     */
    public function addUnion(SQLSelect $query, ?string $type = null): static
    {
        if ($type && $type !== SQLSelect::UNION_ALL && $type !== SQLSelect::UNION_DISTINCT) {
            throw new LogicException('Union $type must be one of the constants UNION_ALL or UNION_DISTINCT.');
        }

        $this->union[] = ['query' => $query, 'type' => $type];
        return $this;
    }

    /**
     * Get all of the queries that will be UNIONed with this one.
     */
    public function getUnions(): array
    {
        return $this->union;
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
     */
    public function addWith(string $name, SQLSelect $query, array $cteFields = [], bool $recursive = false): static
    {
        if (array_key_exists($name, $this->with)) {
            throw new LogicException("WITH clause with name '$name' already exists.");
        }
        $this->with[$name] = [
            'cte_fields' => $cteFields,
            'query' => $query,
            'recursive' => $recursive,
        ];
        return $this;
    }

    /**
     * Get the data which will be used to generate the WITH clause of the query
     */
    public function getWith(): array
    {
        return $this->with;
    }

    /**
     * Return a list of GROUP BY clauses used internally.
     *
     * @return array
     */
    public function getGroupBy()
    {
        return $this->groupby;
    }

    /**
     * Return an itemised select list as a map, where keys are the aliases, and values are the column sources.
     * Aliases will always be provided (if the alias is implicit, the alias value will be inferred), and won't be
     * quoted.
     * E.g., 'Title' => '"SiteTree"."Title"'.
     *
     * @return array
     */
    public function getSelect()
    {
        return $this->select;
    }

    /// VARIOUS TRANSFORMATIONS BELOW

    /**
     * Return the number of rows in this query if the limit were removed.  Useful in paged data sets.
     *
     * @param string $column
     * @return int
     */
    public function unlimitedRowCount($column = null)
    {
        // we can't clear the select if we're relying on its output by a HAVING clause
        if (count($this->having ?? [])) {
            $records = $this->execute();
            return $records->numRecords();
        }

        $clone = clone $this;
        $clone->limit = null;
        $clone->orderby = null;

        // Choose a default column
        if ($column == null) {
            if ($this->groupby) {
                $countQuery = new SQLSelect();
                $countQuery->setSelect("count(*)");
                $countQuery->setFrom(['(' . $clone->sql($innerParameters) . ') all_distinct']);
                $sql = $countQuery->sql($parameters); // $parameters should be empty
                $result = DB::prepared_query($sql, $innerParameters);
                return (int)$result->value();
            } else {
                $clone->setSelect(["count(*)"]);
            }
        } else {
            $clone->setSelect(["count($column)"]);
        }

        $clone->setGroupBy([]);
        return (int)$clone->execute()->value();
    }

    /**
     * Returns true if this query can be sorted by the given field.
     *
     * @param string $fieldName
     * @return bool
     */
    public function canSortBy($fieldName)
    {
        $fieldName = preg_replace('/(\s+?)(A|DE)SC$/', '', $fieldName ?? '');

        return isset($this->select[$fieldName]);
    }


    /**
     * Return the number of rows in this query, respecting limit and offset.
     *
     * @param string $column Quoted, escaped column name
     * @return int
     */
    public function count($column = null)
    {
        // we can't clear the select if we're relying on its output by a HAVING clause
        if (!empty($this->having)) {
            $records = $this->execute();
            return $records->numRecords();
        } elseif ($column == null) {
            // Choose a default column
            if ($this->groupby) {
                $column = 'DISTINCT ' . implode(", ", $this->groupby);
            } else {
                $column = '*';
            }
        }

        $clone = clone $this;
        $clone->select = ['Count' => "count($column)"];
        $clone->limit = null;
        $clone->orderby = null;
        $clone->groupby = null;

        $count = (int)$clone->execute()->value();
        // If there's a limit set, then that limit is going to heavily affect the count
        if ($this->limit) {
            if ($this->limit['limit'] !== null && $count >= ($this->limit['start'] + $this->limit['limit'])) {
                return $this->limit['limit'];
            } else {
                return max(0, $count - $this->limit['start']);
            }

        // Otherwise, the count is going to be the output of the SQL query
        } else {
            return $count;
        }
    }

    /**
     * Return a new SQLSelect that calls the given aggregate functions on this data.
     *
     * @param string $column An aggregate expression, such as 'MAX("Balance")', or a set of them
     * (as an escaped SQL statement)
     * @param string $alias An optional alias for the aggregate column.
     * @return SQLSelect A clone of this object with the given aggregate function
     */
    public function aggregate($column, $alias = null)
    {

        $clone = clone $this;

        // don't set an ORDER BY clause if no limit has been set. It doesn't make
        // sense to add an ORDER BY if there is no limit, and it will break
        // queries to databases like MSSQL if you do so. Note that the reason
        // this came up is because DataQuery::initialiseQuery() introduces
        // a default sort.
        if ($this->limit) {
            $clone->setLimit($this->limit);
            $clone->setOrderBy($this->orderby);
        } else {
            $clone->setOrderBy([]);
        }

        $clone->setGroupBy($this->groupby);
        if ($alias) {
            $clone->setSelect([]);
            $clone->selectField($column, $alias);
        } else {
            $clone->setSelect($column);
        }

        return $clone;
    }

    /**
     * Returns a query that returns only the first row of this query
     *
     * @return SQLSelect A clone of this object with the first row only
     */
    public function firstRow()
    {
        $query = clone $this;
        $offset = $this->limit ? $this->limit['start'] : 0;
        $query->setLimit(1, $offset);
        return $query;
    }

    /**
     * Returns a query that returns only the last row of this query
     *
     * @return SQLSelect A clone of this object with the last row only
     */
    public function lastRow()
    {
        $query = clone $this;
        $offset = $this->limit ? $this->limit['start'] : 0;

        // Limit index to start in case of empty results
        $index = max($this->count() + $offset - 1, 0);
        $query->setLimit(1, $index);
        return $query;
    }

    public function isEmpty()
    {
        // Empty if there's no select, or we're trying to select '*' but there's no FROM clause
        return empty($this->select) || (empty($this->from) && array_key_exists('*', $this->select));
    }
}
