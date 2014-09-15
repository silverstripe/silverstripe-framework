<?php

/**
 * Builds a SQL query string from a SQLExpression object
 *
 * @package framework
 * @subpackage model
 */
class DBQueryBuilder {

	/**
	 * Determines the line separator to use.
	 *
	 * @return string Non-empty whitespace character
	 */
	public function getSeparator() {
		return "\n ";
	}

	/**
	 * Builds a sql query with the specified connection
	 *
	 * @param SQLExpression $query The expression object to build from
	 * @param array $parameters Out parameter for the resulting query parameters
	 * @return string The resulting SQL as a string
	 */
	public function buildSQL(SQLExpression $query, &$parameters) {
		$sql = null;
		$parameters = array();

		// Ignore null queries
		if($query->isEmpty()) return null;

		if($query instanceof SQLSelect) {
			$sql = $this->buildSelectQuery($query, $parameters);
		} elseif($query instanceof SQLDelete) {
			$sql = $this->buildDeleteQuery($query, $parameters);
		} elseif($query instanceof SQLInsert) {
			$sql = $this->buildInsertQuery($query, $parameters);
		} elseif($query instanceof SQLUpdate) {
			$sql = $this->buildUpdateQuery($query, $parameters);
		} else {
			user_error("Not implemented: query generation for type " . $query->getType());
		}
		return $sql;
	}

	/**
	 * Builds a query from a SQLSelect expression
	 *
	 * @param SQLSelect $query The expression object to build from
	 * @param array $parameters Out parameter for the resulting query parameters
	 * @return string Completed SQL string
	 */
	protected function buildSelectQuery(SQLSelect $query, array &$parameters) {
		$sql  = $this->buildSelectFragment($query, $parameters);
		$sql .= $this->buildFromFragment($query, $parameters);
		$sql .= $this->buildWhereFragment($query, $parameters);
		$sql .= $this->buildGroupByFragment($query, $parameters);
		$sql .= $this->buildHavingFragment($query, $parameters);
		$sql .= $this->buildOrderByFragment($query, $parameters);
		$sql .= $this->buildLimitFragment($query, $parameters);
		return $sql;
	}

	/**
	 * Builds a query from a SQLDelete expression
	 *
	 * @param SQLDelete $query The expression object to build from
	 * @param array $parameters Out parameter for the resulting query parameters
	 * @return string Completed SQL string
	 */
	protected function buildDeleteQuery(SQLDelete $query, array &$parameters) {
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
	protected function buildInsertQuery(SQLInsert $query, array &$parameters) {
		$nl = $this->getSeparator();
		$into = $query->getInto();

		// Column identifiers
		$columns = $query->getColumns();
		$sql = "INSERT INTO {$into}{$nl}(" . implode(', ', $columns) . ")";

		// Values
		$sql .= "{$nl}VALUES";

		// Build all rows
		$rowParts = array();
		foreach($query->getRows() as $row) {
			// Build all columns in this row
			$assignments = $row->getAssignments();
			// Join SET components together, considering parameters
			$parts = array();
			foreach($columns as $column) {
				// Check if this column has a value for this row
				if(isset($assignments[$column])) {
					// Assigment is a single item array, expand with a loop here
					foreach($assignments[$column] as $assignmentSQL => $assignmentParameters) {
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
	protected function buildUpdateQuery(SQLUpdate $query, array &$parameters) {
		$sql  = $this->buildUpdateFragment($query, $parameters);
		$sql .= $this->buildWhereFragment($query, $parameters);
		return $sql;
	}

	/**
	 * Returns the SELECT clauses ready for inserting into a query.
	 *
	 * @param SQLSelect $query The expression object to build from
	 * @param array $parameters Out parameter for the resulting query parameters
	 * @return string Completed select part of statement
	 */
	protected function buildSelectFragment(SQLSelect $query, array &$parameters) {
		$distinct = $query->getDistinct();
		$select = $query->getSelect();
		$clauses = array();

		foreach ($select as $alias => $field) {
			// Don't include redundant aliases.
			$fieldAlias = "\"{$alias}\"";
			if ($alias === $field || substr($field, -strlen($fieldAlias)) === $fieldAlias) {
				$clauses[] = $field;
			} else {
				$clauses[] = "$field AS $fieldAlias";
			}
		}

		$text = 'SELECT ';
		if ($distinct) $text .= 'DISTINCT ';
		return $text .= implode(', ', $clauses);
	}

	/**
	 * Return the DELETE clause ready for inserting into a query.
	 *
	 * @param SQLExpression $query The expression object to build from
	 * @param array $parameters Out parameter for the resulting query parameters
	 * @return string Completed delete part of statement
	 */
	public function buildDeleteFragment(SQLDelete $query, array &$parameters) {
		$text = 'DELETE';

		// If doing a multiple table delete then list the target deletion tables here
		// Note that some schemas don't support multiple table deletion
		$delete = $query->getDelete();
		if(!empty($delete)) {
			$text .= ' ' . implode(', ', $delete);
		}
		return $text;
	}

	/**
	 * Return the UPDATE clause ready for inserting into a query.
	 *
	 * @param SQLExpression $query The expression object to build from
	 * @param array $parameters Out parameter for the resulting query parameters
	 * @return string Completed from part of statement
	 */
	public function buildUpdateFragment(SQLUpdate $query, array &$parameters) {
		$table = $query->getTable();
		$text = "UPDATE $table";

		// Join SET components together, considering parameters
		$parts = array();
		foreach($query->getAssignments() as $column => $assignment) {
			// Assigment is a single item array, expand with a loop here
			foreach($assignment as $assignmentSQL => $assignmentParameters) {
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
	 * @param SQLExpression $query The expression object to build from
	 * @param array $parameters Out parameter for the resulting query parameters
	 * @return string Completed from part of statement
	 */
	public function buildFromFragment(SQLConditionalExpression $query, array &$parameters) {
		$from = $query->getJoins($joinParameters);
		$parameters = array_merge($parameters, $joinParameters);
		$nl = $this->getSeparator();
		return  "{$nl}FROM " . implode(' ', $from);
	}

	/**
	 * Returns the WHERE clauses ready for inserting into a query.
	 *
	 * @param SQLExpression $query The expression object to build from
	 * @param array $parameters Out parameter for the resulting query parameters
	 * @return string Completed where condition
	 */
	public function buildWhereFragment(SQLConditionalExpression $query, array &$parameters) {
		// Get parameterised elements
		$where = $query->getWhereParameterised($whereParameters);
		if(empty($where)) return '';

		// Join conditions
		$connective = $query->getConnective();
		$parameters = array_merge($parameters, $whereParameters);
		$nl = $this->getSeparator();
		return "{$nl}WHERE (" . implode("){$nl}{$connective} (", $where) . ")";
	}

	/**
	 * Returns the ORDER BY clauses ready for inserting into a query.
	 *
	 * @param SQLSelect $query The expression object to build from
	 * @param array $parameters Out parameter for the resulting query parameters
	 * @return string Completed order by part of statement
	 */
	public function buildOrderByFragment(SQLSelect $query, array &$parameters) {
		$orderBy = $query->getOrderBy();
		if(empty($orderBy)) return '';

		// Build orders, each with direction considered
		$statements = array();
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
	public function buildGroupByFragment(SQLSelect $query, array &$parameters) {
		$groupBy = $query->getGroupBy();
		if(empty($groupBy)) return '';

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
	public function buildHavingFragment(SQLSelect $query, array &$parameters) {
		$having = $query->getHavingParameterised($havingParameters);
		if(empty($having)) return '';

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
	public function buildLimitFragment(SQLSelect $query, array &$parameters) {
		$nl = $this->getSeparator();

		// Ensure limit is given
		$limit = $query->getLimit();
		if(empty($limit)) return '';

		// For literal values return this as the limit SQL
		if (!is_array($limit)) {
			return "{$nl}LIMIT $limit";
		}

		// Assert that the array version provides the 'limit' key
		if (!isset($limit['limit']) || !is_numeric($limit['limit'])) {
			throw new InvalidArgumentException(
				'DBQueryBuilder::buildLimitSQL(): Wrong format for $limit: '. var_export($limit, true)
			);
		}

		// Format the array limit, given an optional start key
		$clause = "{$nl}LIMIT {$limit['limit']}";
		if(isset($limit['start']) && is_numeric($limit['start']) && $limit['start'] !== 0) {
			$clause .= " OFFSET {$limit['start']}";
		}
		return $clause;
	}
}
