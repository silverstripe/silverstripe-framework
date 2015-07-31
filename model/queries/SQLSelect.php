<?php

/**
 * Object representing a SQL SELECT query.
 * The various parts of the SQL query can be manipulated individually.
 *
 * @package framework
 * @subpackage model
 */
class SQLSelect extends SQLConditionalExpression {
	
	/**
	 * An array of SELECT fields, keyed by an optional alias.
	 *
	 * @var array
	 */
	protected $select = array();

	/**
	 * An array of GROUP BY clauses.
	 *
	 * @var array
	 */
	protected $groupby = array();

	/**
	 * An array of having clauses.
	 * Each item in this array will be in the form of a single-length array
	 * in the format array('predicate' => array($parameters))
	 *
	 * @var array
	 */
	protected $having = array();

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
	 * @var string
	 */
	protected $orderby = array();

	/**
	 * An array containing limit and offset keys for LIMIT clause.
	 *
	 * May be used on SELECT or single table DELETE queries in some adapters
	 *
	 * @var array
	 */
	protected $limit = array();

	/**
	 * Construct a new SQLSelect.
	 *
	 * @param array $select An array of SELECT fields.
	 * @param array|string $from An array of FROM clauses. The first one should be just the table name.
	 * Each should be ANSI quoted.
	 * @param array $where An array of WHERE clauses.
	 * @param array $orderby An array ORDER BY clause.
	 * @param array $groupby An array of GROUP BY clauses.
	 * @param array $having An array of HAVING clauses.
	 * @param array|string $limit A LIMIT clause or array with limit and offset keys
	 * @return static
	 */
	public static function create($select = "*", $from = array(), $where = array(), $orderby = array(),
			$groupby = array(), $having = array(), $limit = array()) {
		return Injector::inst()->createWithArgs(__CLASS__, func_get_args());
	}

	/**
	 * Construct a new SQLSelect.
	 *
	 * @param array $select An array of SELECT fields.
	 * @param array|string $from An array of FROM clauses. The first one should be just the table name.
	 * Each should be ANSI quoted.
	 * @param array $where An array of WHERE clauses.
	 * @param array $orderby An array ORDER BY clause.
	 * @param array $groupby An array of GROUP BY clauses.
	 * @param array $having An array of HAVING clauses.
	 * @param array|string $limit A LIMIT clause or array with limit and offset keys
	 */
	public function __construct($select = "*", $from = array(), $where = array(), $orderby = array(),
			$groupby = array(), $having = array(), $limit = array()) {

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
	 *  $query->setSelect(array('"Col1"', '"Col2"'))->setFrom('"MyTable"');
	 *
	 *  // pass fields to select as multiple parameters
	 *  $query->setSelect('"Col1"', '"Col2"')->setFrom('"MyTable"');
	 *
	 *  // Set a list of selected fields as aliases
	 *  $query->setSelect(array('Name' => '"Col1"', 'Details' => '"Col2"')->setFrom('"MyTable"');
	 * </code>
	 *
	 * @param string|array $fields Field names should be ANSI SQL quoted. Array keys should be unquoted.
	 * @param boolean $clear Clear existing select fields?
	 * @return $this Self reference
	 */
	public function setSelect($fields) {
		$this->select = array();
		if (func_num_args() > 1) {
			$fields = func_get_args();
		} else if(!is_array($fields)) {
			$fields = array($fields);
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
	public function addSelect($fields) {
		if (func_num_args() > 1) {
			$fields = func_get_args();
		} else if(!is_array($fields)) {
			$fields = array($fields);
		}
		foreach($fields as $idx => $field) {
			if(preg_match('/^(.*) +AS +"([^"]*)"/i', $field, $matches)) {
				Deprecation::notice("3.0", "Use selectField() to specify column aliases");
				$this->selectField($matches[1], $matches[2]);
			} else {
				$this->selectField($field, is_numeric($idx) ? null : $idx);
			}
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
	public function selectField($field, $alias = null) {
		if(!$alias) {
			if(preg_match('/"([^"]+)"$/', $field, $matches)) $alias = $matches[1];
			else $alias = $field;
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
	public function expressionForField($field) {
		return isset($this->select[$field]) ? $this->select[$field] : null;
	}

	/**
	 * Set distinct property.
	 *
	 * @param bool $value
	 * @return self Self reference
	 */
	public function setDistinct($value) {
		$this->distinct = $value;
		return $this;
	}

	/**
	 * Get the distinct property.
	 *
	 * @return bool
	 */
	public function getDistinct() {
		return $this->distinct;
	}

	/**
	 * Get the limit property.
	 * @return array
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * Pass LIMIT clause either as SQL snippet or in array format.
	 * Internally, limit will always be stored as a map containing the keys 'start' and 'limit'
	 *
	 * @param int|string|array $limit If passed as a string or array, assumes SQL escaped data.
	 * Only applies for positive values, or if an $offset is set as well.
	 * @param int $offset
	 * @throws InvalidArgumentException
	 * @return self Self reference
	 */
	public function setLimit($limit, $offset = 0) {
		if((is_numeric($limit) && $limit < 0) || (is_numeric($offset) && $offset < 0)) {
			throw new InvalidArgumentException("SQLSelect::setLimit() only takes positive values");
		}

		if(is_numeric($limit) && ($limit || $offset)) {
			$this->limit = array(
				'start' => $offset,
				'limit' => $limit,
			);
		} else if($limit && is_string($limit)) {
			if(strpos($limit, ',') !== false) {
				list($start, $innerLimit) = explode(',', $limit, 2);
			} else {
				list($innerLimit, $start) = explode(' OFFSET ', strtoupper($limit), 2);
			}

			$this->limit = array(
				'start' => trim($start),
				'limit' => trim($innerLimit),
			);
		} else if($limit === null && $offset) {
			$this->limit = array(
				'start' => $offset,
				'limit' => $limit
			);
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
	 * @example $sql->setOrderBy(array("Column" => "ASC", "ColumnTwo" => "DESC"));
	 *
	 * @param string|array $clauses Clauses to add (escaped SQL statement)
	 * @param string $direction Sort direction, ASC or DESC
	 *
	 * @return $this Self reference
	 */
	public function setOrderBy($clauses = null, $direction = null) {
		$this->orderby = array();
		return $this->addOrderBy($clauses, $direction);
	}

	/**
	 * Add ORDER BY clause either as SQL snippet or in array format.
	 *
	 * @example $sql->addOrderBy("Column");
	 * @example $sql->addOrderBy("Column DESC");
	 * @example $sql->addOrderBy("Column DESC, ColumnTwo ASC");
	 * @example $sql->addOrderBy("Column", "DESC");
	 * @example $sql->addOrderBy(array("Column" => "ASC", "ColumnTwo" => "DESC"));
	 *
	 * @param string|array $clauses Clauses to add (escaped SQL statements)
	 * @param string $direction Sort direction, ASC or DESC
	 * @return $this Self reference
	 */
	public function addOrderBy($clauses = null, $direction = null) {
		if(empty($clauses)) return $this;

		if(is_string($clauses)) {
			if(strpos($clauses, "(") !== false) {
				$sort = preg_split("/,(?![^()]*+\\))/", $clauses);
			} else {
				$sort = explode(",", $clauses);
			}

			$clauses = array();

			foreach($sort as $clause) {
				list($column, $direction) = $this->getDirectionFromString($clause, $direction);
				$clauses[$column] = $direction;
			}
		}

		if(is_array($clauses)) {
			foreach($clauses as $key => $value) {
				if(!is_numeric($key)) {
					$column = trim($key);
					$columnDir = strtoupper(trim($value));
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
		if($this->orderby) {
			$i = 0;
			$orderby = array();
			foreach($this->orderby as $clause => $dir) {

				// public function calls and multi-word columns like "CASE WHEN ..."
				if(strpos($clause, '(') !== false || strpos($clause, " ") !== false ) {

					// Move the clause to the select fragment, substituting a placeholder column in the sort fragment.
					$clause = trim($clause);
					$column = "_SortColumn{$i}";
					$this->selectField($clause, $column);
					$clause = '"' . $column . '"';
					$i++;
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
	 * @return array A two element array: array($column, $direction)
	 */
	private function getDirectionFromString($value, $defaultDirection = null) {
		if(preg_match('/^(.*)(asc|desc)$/i', $value, $matches)) {
			$column = trim($matches[1]);
			$direction = strtoupper($matches[2]);
		} else {
			$column = $value;
			$direction = $defaultDirection ? $defaultDirection : "ASC";
		}
		return array($column, $direction);
	}

	/**
	 * Returns the current order by as array if not already. To handle legacy
	 * statements which are stored as strings. Without clauses and directions,
	 * convert the orderby clause to something readable.
	 *
	 * @return array
	 */
	public function getOrderBy() {
		$orderby = $this->orderby;
		if(!$orderby) $orderby = array();

		if(!is_array($orderby)) {
			// spilt by any commas not within brackets
			$orderby = preg_split('/,(?![^()]*+\\))/', $orderby);
		}

		foreach($orderby as $k => $v) {
			if(strpos($v, ' ') !== false) {
				unset($orderby[$k]);

				$rule = explode(' ', trim($v));
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
	 * @return self Self reference
	 */
	public function reverseOrderBy() {
		$order = $this->getOrderBy();
		$this->orderby = array();

		foreach($order as $clause => $dir) {
			$dir = (strtoupper($dir) == 'DESC') ? 'ASC' : 'DESC';
			$this->addOrderBy($clause, $dir);
		}

		return $this;
	}

	/**
	 * Set a GROUP BY clause.
	 *
	 * @param string|array $groupby Escaped SQL statement
	 * @return self Self reference
	 */
	public function setGroupBy($groupby) {
		$this->groupby = array();
		return $this->addGroupBy($groupby);
	}

	/**
	 * Add a GROUP BY clause.
	 *
	 * @param string|array $groupby Escaped SQL statement
	 * @return self Self reference
	 */
	public function addGroupBy($groupby) {
		if(is_array($groupby)) {
			$this->groupby = array_merge($this->groupby, $groupby);
		} elseif(!empty($groupby)) {
			$this->groupby[] = $groupby;
		}

		return $this;
	}

	/**
	 * Set a HAVING clause.
	 *
	 * @see SQLSelect::addWhere() for syntax examples
	 *
	 * @param mixed $having Predicate(s) to set, as escaped SQL statements or paramaterised queries
	 * @param mixed $having,... Unlimited additional predicates
	 * @return self Self reference
	 */
	public function setHaving($having) {
		$having = func_num_args() > 1 ? func_get_args() : $having;
		$this->having = array();
		return $this->addHaving($having);
	}

	/**
	 * Add a HAVING clause
	 *
	 * @see SQLSelect::addWhere() for syntax examples
	 *
	 * @param mixed $having Predicate(s) to set, as escaped SQL statements or paramaterised queries
	 * @param mixed $having,... Unlimited additional predicates
	 * @return self Self reference
	 */
	public function addHaving($having) {
		$having = $this->normalisePredicates(func_get_args());

		// If the function is called with an array of items
		$this->having = array_merge($this->having, $having);

		return $this;
	}

	/**
	 * Return a list of HAVING clauses used internally.
	 * @return array
	 */
	public function getHaving() {
		return $this->having;
	}

	/**
	 * Return a list of HAVING clauses used internally.
	 *
	 * @param array $parameters Out variable for parameters required for this query
	 * @return array
	 */
	public function getHavingParameterised(&$parameters) {
		$this->splitQueryParameters($this->having, $conditions, $parameters);
		return $conditions;
	}

	/**
	 * Return a list of GROUP BY clauses used internally.
	 *
	 * @return array
	 */
	public function getGroupBy() {
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
	public function getSelect() {
		return $this->select;
	}

	/// VARIOUS TRANSFORMATIONS BELOW

	/**
	 * Return the number of rows in this query if the limit were removed.  Useful in paged data sets.
	 *
	 * @param string $column
	 * @return int
	 */
	public function unlimitedRowCount($column = null) {
		// we can't clear the select if we're relying on its output by a HAVING clause
		if(count($this->having)) {
			$records = $this->execute();
			return $records->numRecords();
		}

		$clone = clone $this;
		$clone->limit = null;
		$clone->orderby = null;

		// Choose a default column
		if($column == null) {
			if($this->groupby) {
				// @todo Test case required here
				$countQuery = new SQLSelect();
				$countQuery->setSelect("count(*)");
				$countQuery->setFrom(array('(' . $clone->sql($innerParameters) . ') all_distinct'));
				$sql = $countQuery->sql($parameters); // $parameters should be empty
				$result = DB::prepared_query($sql, $innerParameters);
				return $result->value();
			} else {
				$clone->setSelect(array("count(*)"));
			}
		} else {
			$clone->setSelect(array("count($column)"));
		}

		$clone->setGroupBy(array());
		return $clone->execute()->value();
	}

	/**
	 * Returns true if this query can be sorted by the given field.
	 *
	 * @param string $fieldName
	 * @return bool
	 */
	public function canSortBy($fieldName) {
		$fieldName = preg_replace('/(\s+?)(A|DE)SC$/', '', $fieldName);

		return isset($this->select[$fieldName]);
	}


	/**
	 * Return the number of rows in this query if the limit were removed.  Useful in paged data sets.
	 *
	 * @todo Respect HAVING and GROUPBY, which can affect the result-count
	 *
	 * @param string $column Quoted, escaped column name
	 * @return int
	 */
	public function count( $column = null) {
		// we can't clear the select if we're relying on its output by a HAVING clause
		if(!empty($this->having)) {
			$records = $this->execute();
			return $records->numRecords();
		}
		// Choose a default column
		elseif($column == null) {
			if($this->groupby) {
				$column = 'DISTINCT ' . implode(", ", $this->groupby);
			} else {
				$column = '*';
			}
		}

		$clone = clone $this;
		$clone->select = array('Count' => "count($column)");
		$clone->limit = null;
		$clone->orderby = null;
		$clone->groupby = null;

		$count = $clone->execute()->value();
		// If there's a limit set, then that limit is going to heavily affect the count
		if($this->limit) {
			if($this->limit['limit'] !== null && $count >= ($this->limit['start'] + $this->limit['limit'])) {
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
	public function aggregate($column, $alias = null) {

		$clone = clone $this;

		// don't set an ORDER BY clause if no limit has been set. It doesn't make
		// sense to add an ORDER BY if there is no limit, and it will break
		// queries to databases like MSSQL if you do so. Note that the reason
		// this came up is because DataQuery::initialiseQuery() introduces
		// a default sort.
		if($this->limit) {
			$clone->setLimit($this->limit);
			$clone->setOrderBy($this->orderby);
		} else {
			$clone->setOrderBy(array());
		}

		$clone->setGroupBy($this->groupby);
		if($alias) {
			$clone->setSelect(array());
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
	public function firstRow() {
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
	public function lastRow() {
		$query = clone $this;
		$offset = $this->limit ? $this->limit['start'] : 0;

		// Limit index to start in case of empty results
		$index = max($this->count() + $offset - 1, 0);
		$query->setLimit(1, $index);
		return $query;
	}
}
