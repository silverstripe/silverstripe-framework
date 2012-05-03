<?php
/**
 * Object representing a SQL query.
 * The various parts of the SQL query can be manipulated individually.
 * 
 * Caution: Only supports SELECT (default) and DELETE at the moment.
 * 
 * @todo Add support for INSERT and UPDATE queries
 * 
 * @package framework
 * @subpackage model
 */
class SQLQuery {
	
	/**
	 * An array of fields to select, keyed by an optional alias.
	 *
	 * @var array
	 */
	public $select = array();
	
	/**
	 * An array of join clauses. The first one is just the table name.
	 *
	 * @var array
	 */
	public $from = array();
	
	/**
	 * An array of filters.
	 *
	 * @var array
	 */
	public $where = array();
	
	/**
	 * An array of order by clauses, functions. Stores as an associative
	 * array of column / function to direction.
	 *
	 * @var string
	 */
	public $orderby = array();
	
	/**
	 * An array of fields to group by.
	 *
	 * @var array
	 */
	public $groupby = array();
	
	/**
	 * An array of having clauses.
	 *
	 * @var array
	 */
	public $having = array();
	
	/**
	 * A limit clause.
	 *
	 * @var string
	 */
	public $limit;
	
	/**
	 * If this is true DISTINCT will be added to the SQL.
	 * @var boolean
	 */
	public $distinct = false;
	
	/**
	 * If this is true, this statement will delete rather than select.
	 *
	 * @var boolean
	 */
	public $delete = false;
	
	/**
	 * The logical connective used to join WHERE clauses. Defaults to AND.
	 *
	 * @var string
	 */
	public $connective = 'AND';
	
	/**
	 * Keep an internal register of find/replace pairs to execute when it's time to actually get the
	 * query SQL.
	 */
	private $replacementsOld = array(), $replacementsNew = array();
	
	/**
	 * Construct a new SQLQuery.
	 * 
	 * @param array $select An array of fields to select.
	 * @param array $from An array of join clauses. The first one should be just the table name.
	 * @param array $where An array of filters, to be inserted into the WHERE clause.
	 * @param string $orderby An ORDER BY clause.
	 * @param array $groupby An array of fields to group by.
	 * @param array $having An array of having clauses.
	 * @param string $limit A LIMIT clause.
	 * 
	 * TODO: perhaps we can quote things here instead of requiring all the parameters to be quoted
	 * by this stage.
	 */
	function __construct($select = "*", $from = array(), $where = "", $orderby = "", $groupby = "", $having = "", $limit = "") {
		$this->select($select);
		// @todo 
		$this->from = is_array($from) ? $from : array(str_replace(array('"','`'),'',$from) => $from);
		$this->where($where);
		$this->orderby($orderby);
		$this->groupby($groupby);
		$this->having($having);
		$this->limit($limit);
	}
	
	/**
	 * Clear the selected fields to start over
	 */
	function clearSelect() {
		$this->select = array();
		return $this;
	}
	
	/**
	 * Specify the list of columns to be selected by the query.
	 *
	 * <code>
	 *  // pass fields to select as single parameter array
	 *  $query->select(array("Col1","Col2"))->from("MyTable");
	 * 
	 *  // pass fields to select as multiple parameters
	 *  $query->select("Col1", "Col2")->from("MyTable");
	 * </code>
	 * 
	 * @param mixed $fields
	 * @return SQLQuery
	 */
	public function select($fields) {
		if (func_num_args() > 1) {
			$fields = func_get_args();
		} else if(!is_array($fields)) {
			$fields = array($fields);
		}

		$this->select = array();
		$this->selectMore($fields);
		
		return $this;
	}
	
	/**
	 * Add addition columns to the select clause
	 *
	 * @param array|string
	 */
	public function selectMore($fields) {
		if (func_num_args() > 1) {
			$fields = func_get_args();
		} else if(!is_array($fields)) {
			$fields = array($fields);
		}
		
		$this->select = array();
		foreach($fields as $idx => $field) {
			if(preg_match('/^(.*) +AS +"?([^"]*)"?/i', $field, $matches)) {
				Deprecation::notice("3.0", "Use selectField() to specify column aliases");
				$this->selectField($matches[1], $matches[2]);
			} else {
				$this->selectField($field, is_numeric($idx) ? null : $idx);
			}
		}
	}
	
	/**
	 * Select an additional field
	 *
	 * @param $field The field to select
	 * @param $alias The alias of that field
	 */
	public function selectField($field, $alias = null) {
		if(!$alias) {
			if(preg_match('/"([^"]+)"$/', $field, $matches)) $alias = $matches[1];
			else $alias = $field;
		}
		$this->select[$alias] = $field;
	}

	/**
	 * Return the SQL expression for the given field alias.
	 * Returns null if the given alias doesn't exist.
	 */
	public function expressionForField($field) {
		return isset($this->select[$field]) ? $this->select[$field] : null;
	}
	
	/**
	 * Specify the target table to select from.
	 * 
	 * <code>
	 *  $query->from("MyTable"); // SELECT * FROM MyTable
	 * </code>
	 *
	 * @param string $table
	 * @return SQLQuery This instance
	 */
	public function from($table) {
		$this->from[str_replace(array('"','`'),'',$table)] = $table;
		
		return $this;
	}
	
	/**
	 * Add a LEFT JOIN criteria to the FROM clause.
	 * 
	 * @param String $table Table name (unquoted)
	 * @param String $onPredicate The "ON" SQL fragment in a "LEFT JOIN ... AS ... ON ..." statement.
	 *  Needs to be valid (quoted) SQL.
	 * @param String $tableAlias Optional alias which makes it easier to identify and replace joins later on
	 * @return SQLQuery This instance 
	 */
	public function leftJoin($table, $onPredicate, $tableAlias=null) {
		if( !$tableAlias ) {
			$tableAlias = $table;
		}
		$this->from[$tableAlias] = array('type' => 'LEFT', 'table' => $table, 'filter' => array($onPredicate));
		return $this;
	}
	
	/**
	 * Add an INNER JOIN criteria to the FROM clause.
	 * 
	 * @param String $table Table name (unquoted)
	 * @param String $onPredicate The "ON" SQL fragment in a "LEFT JOIN ... AS ... ON ..." statement.
	 *  Needs to be valid (quoted) SQL.
	 * @param String $tableAlias Optional alias which makes it easier to identify and replace joins later on
	 * @return SQLQuery This instance 
	 */
	public function innerJoin($table, $onPredicate, $tableAlias=null) {
		if( !$tableAlias ) {
			$tableAlias = $table;
		}
		$this->from[$tableAlias] = array('type' => 'INNER', 'table' => $table, 'filter' => array($onPredicate));
		return $this;
	}
	
	/**
	 * Add an additional filter (part of the ON clause) on a join
	 */
	public function addFilterToJoin($tableAlias, $filter) {
		$this->from[$tableAlias]['filter'][] = $filter;
	}

	/**
	 * Replace the existing filter (ON clause) on a join
	 */
	public function setJoinFilter($tableAlias, $filter) {
		if(is_string($this->from[$tableAlias])) {Debug::message($tableAlias); Debug::dump($this->from);}
		$this->from[$tableAlias]['filter'] = array($filter);
	}
	
	/**
	 * Returns true if we are already joining to the given table alias
	 */
	public function isJoinedTo($tableAlias) {
		return isset($this->from[$tableAlias]);
	}
	
	/**
	 * Return a list of tables that this query is selecting from.
	 */
	public function queriedTables() {
		$tables = array();
		
		foreach($this->from as $key => $tableClause) {
			if(is_array($tableClause)) $table = '"'.$tableClause['table'].'"';
			else if(is_string($tableClause) && preg_match('/JOIN +("[^"]+") +(AS|ON) +/i', $tableClause, $matches)) $table = $matches[1];
			else $table = $tableClause;

			// Handle string replacements
			if($this->replacementsOld) $table = str_replace($this->replacementsOld, $this->replacementsNew, $table);
			
			$tables[] = preg_replace('/^"|"$/','',$table);
		}
		
		return $tables;	
	}

	/**
	 * Pass LIMIT clause either as SQL snippet or in array format.
	 * Internally, limit will always be stored as a map containing the keys 'start' and 'limit'
	 *
	 * @param string|array $limit
	 * @return SQLQuery This instance
	 */
	public function limit($limit, $offset = 0) {
		if($limit && is_numeric($limit)) {
			$this->limit = array(
				'start' => $offset,
				'limit' => $limit,
			);
		} else if($limit && is_string($limit)) {
			if(strpos($limit,',') !== false) list($start, $innerLimit) = explode(',', $limit, 2);
			else list($innerLimit, $start) = explode(' OFFSET ', strtoupper($limit), 2);
			$this->limit = array(
				'start' => trim($start),
				'limit' => trim($innerLimit),
			);
			
		} else {
			$this->limit = $limit;
		}
		
		return $this;
	}
	
	/**
	 * Pass ORDER BY clause either as SQL snippet or in array format.
	 *
	 * @example $sql->orderby("Column");
	 * @example $sql->orderby("Column DESC");
	 * @example $sql->orderby("Column DESC, ColumnTwo ASC");
	 * @example $sql->orderby("Column", "DESC");
	 * @example $sql->orderby(array("Column" => "ASC", "ColumnTwo" => "DESC"));
	 *
	 * @param string|array $orderby
	 * @param string $dir
	 * @param bool $clear remove existing order by clauses
	 *
	 * @return SQLQuery
	 */
	public function orderby($clauses = null, $direction = null, $clear = true) {
		if($clear) $this->orderby = array();

		if(!$clauses) {
			return $this;
		}
		
		if(is_string($clauses)) {
			if(strpos($clauses, "(") !== false) {				
				$sort = preg_split("/,(?![^()]*+\\))/", $clauses);
			}
			else {
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
				}
				else {
					list($column, $columnDir) = $this->getDirectionFromString($value);
				}
				
				$this->orderby[$column] = $columnDir;
			}
		}
		else {
			user_error('SQLQuery::orderby() incorrect format for $orderby', E_USER_WARNING);
		}

		// If sort contains a function call, let's move the sort clause into a 
		// separate selected field.
		//
		// Some versions of MySQL choke if you have a group function referenced 
		// directly in the ORDER BY
		if($this->orderby) {
			$i = 0;
			foreach($this->orderby as $clause => $dir) {
				// Function calls and multi-word columns like "CASE WHEN ..."
				if(strpos($clause, '(') !== false || strpos($clause, " ") !== false ) {
					// remove the old orderby
					unset($this->orderby[$clause]);
					
					$clause = trim($clause);
					$column = "_SortColumn{$i}";

					$this->selectField($clause, $column);
					$this->orderby('"' . $column . '"', $dir, false);
					$i++;
				}
			}
		}
		
		return $this;
	}
	
	/**
	 * Extract the direction part of a single-column order by clause.
	 * 
	 * Return a 2 element array: array($column, $direction)
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
	 * @todo When $orderby is a private variable and all orderby statements
	 *		set through 
	 *
	 * @return array
	 */
	public function getOrderBy() {
		$orderby = $this->orderby;
		
		if(!is_array($orderby)) {
			// spilt by any commas not within brackets
			$orderby = preg_split("/,(?![^()]*+\\))/", $orderby);
		}
		
		foreach($orderby as $k => $v) {
			if(strpos($v, " ") !== false) {
				unset($orderby[$k]);

				$rule = explode(" ", trim($v));
				$clause = $rule[0];
				$dir = (isset($rule[1])) ? $rule[1] : "ASC";

				$orderby[$clause] = $dir;
			}
		}

		return $orderby;
	}
	
	/**
	 * Reverses the order by clause by replacing ASC or DESC references in the
	 * current order by with it's corollary. 
	 *
	 * @return SQLQuery
	 */
	public function reverseOrderBy() {
		$order = $this->getOrderBy();
		
		$this->orderby = array();
		
		foreach($order as $clause => $dir) {
			$dir = (strtoupper($dir) == "DESC") ? "ASC" : "DESC";

			$this->orderby($clause, $dir, false);
		}
		
		return $this;
	}
	
	/**
	 * Add a GROUP BY clause.
	 *
	 * @param string|array $groupby
	 * @return SQLQuery
	 */
	public function groupby($groupby) {
		if(is_array($groupby)) {
			$this->groupby = array_merge($this->groupby, $groupby);  
		} elseif(!empty($groupby)) {
			$this->groupby[] = $groupby;
		}
		
		return $this;
	}

	/**
	 * Add a HAVING clause.
	 *
	 * @param string|array $having
	 * @return SQLQuery
	 */
	public function having($having) {
		if(is_array($having)) {
			$this->having = array_merge($this->having, $having);  
		} elseif(!empty($having)) {
			$this->having[] = $having;
		}
		
		return $this;
	}
	
	/**
	 * Apply a predicate filter to the where clause.
	 * 
	 * Accepts a variable length of arguments, which represent
	 * different ways of formatting a predicate in a where clause:
	 * 
	 * <code>
	 *  // the entire predicate as a single string
	 *  $query->where("Column = 'Value'");
	 * 
	 *  // an exact match predicate with a key value pair
	 *  $query->where("Column", "Value");
	 * 
	 *  // a predicate with user defined operator
	 *  $query->where("Column", "!=", "Value");
	 * </code>
	 * 
	 */
	public function where() {
		$args = func_get_args();
		if (func_num_args() == 3) {
			$filter = "{$args[0]} {$args[1]} '{$args[2]}'";
		} elseif (func_num_args() == 2) {
			$filter = "{$args[0]} = '{$args[1]}'";
		} else {
			$filter = $args[0];
		}
		
		if(is_array($filter)) {
			$this->where = array_merge($this->where,$filter);
		} elseif(!empty($filter)) {
			$this->where[] = $filter;
		}
		
		return $this;
	}

	/**
	 *
	 */
	function whereAny($filters) {
		if(is_string($filters)) $filters = func_get_args();
		$clause = implode(" OR ", $filters);
		return $this->where($clause);
	}
		
	/**
	 * Use the disjunctive operator 'OR' to join filter expressions in the WHERE clause.
	 */
	public function useDisjunction() {
		$this->connective = 'OR';
	}

	/**
	 * Use the conjunctive operator 'AND' to join filter expressions in the WHERE clause.
	 */
	public function useConjunction() {
		$this->connective = 'AND';
	}
	
	/**
	 * Swap the use of one table with another.
	 * @param string $old Name of the old table.
	 * @param string $new Name of the new table.
	 */
	function renameTable($old, $new) {
		$this->replaceText("`$old`", "`$new`");
		$this->replaceText("\"$old\"", "\"$new\"");
	}
	
	/**
	 * Swap some text in the SQL query with another.
	 * @param string $old The old text.
	 * @param string $new The new text.
	 */
	function replaceText($old, $new) {
		$this->replacementsOld[] = $old;
		$this->replacementsNew[] = $new;
	}

	public function getFilter() {
		Deprecation::notice('3.0', 'Please use prepareWhere() instead of getFilter()');
		return $this->prepareWhere();
	}
	
	/**
	 * Return an itemised select list as a map, where keys are the aliases, and values are the column sources.
	 * Aliases will always be provided (if the alias is implicit, the alias value will be inferred), and won't be quoted.
	 * E.g., 'Title' => '"SiteTree"."Title"'.
	 */
	public function itemisedSelect() {
		return $this->select;
	}

	/**
	 * Returns the WHERE clauses ready for inserting into a query.
	 * @return string
	 */
	public function prepareSelect() {
		$clauses = array();
		foreach($this->select as $alias => $field) {
			// Don't include redundant aliases.
			if($alias === $field || preg_match('/"' . preg_quote($alias) . '"$/', $field)) $clauses[] = $field;
			else $clauses[] = "$field AS \"$alias\"";
		}
		return implode(", ", $clauses);
	}

	/**
	 * Returns the WHERE clauses ready for inserting into a query.
	 * @return string
	 */
	public function prepareWhere() {
		return ($this->where) ? implode(") {$this->connective} (" , $this->where) : '';
	}
	
	/**
	 * Returns the ORDER BY clauses ready for inserting into a query.
	 * @return string
	 */
	public function prepareOrderBy() {
		$statments = array();
			
		if($order = $this->getOrderBy()) {
			foreach($order as $clause => $dir) {
				$statements[] = trim($clause . ' '. $dir);
			}
		}
		
		return implode(", ", $statements);
	}
	
	/**
	 * Returns the GROUP BY clauses ready for inserting into a query.
	 * @return string
	 */
	public function prepareGroupBy() {
		return implode(", ", $this->groupby);
	}
	
	/**
	 * Returns the HAVING clauses ready for inserting into a query.
	 * @return string
	 */
	public function prepareHaving() {
		return  implode(" ) AND ( ", $sqlQuery->having);
	}
	
	
	/**
	 * Generate the SQL statement for this query.
	 * 
	 * @return string
	 */
	function sql() {
		// TODO: Don't require this internal-state manipulate-and-preserve - let sqlQueryToString() handle the new syntax
		$origFrom = $this->from;

		// Build from clauses
		foreach($this->from as $alias => $join) {
			// $join can be something like this array structure
			// array('type' => 'inner', 'table' => 'SiteTree', 'filter' => array("SiteTree.ID = 1", "Status = 'approved'"))
			if(is_array($join)) {
				if(is_string($join['filter'])) $filter = $join['filter'];
				else if(sizeof($join['filter']) == 1) $filter = $join['filter'][0];
				else $filter = "(" . implode(") AND (", $join['filter']) . ")";

				$aliasClause = ($alias != $join['table']) ? " AS \"$alias\"" : "";
				$this->from[$alias] = strtoupper($join['type']) . " JOIN \"{$join['table']}\"$aliasClause ON $filter";
			}
		}

		$sql = DB::getConn()->sqlQueryToString($this);
		
		if($this->replacementsOld) {
			$sql = str_replace($this->replacementsOld, $this->replacementsNew, $sql);
		}

		$this->from = $origFrom;

		// The query was most likely just created and then exectued.
		if($sql === 'SELECT *') {
			return '';
		}

		return $sql;
	}
	
	/**
	 * Return the generated SQL string for this query
	 * 
	 * @return string
	 */
	function __toString() {
	    try {
		    return $this->sql();
	    } catch(Exception $e) {
	        return "<sql query>";
	    }
	}
	
	/**
	 * Execute this query.
	 * @return SS_Query
	 */
	function execute() {
		return DB::query($this->sql(), E_USER_ERROR);
	}
	
	/**
	 * Checks whether this query is for a specific ID in a table
	 * 
	 * @todo Doesn't work with combined statements (e.g. "Foo='bar' AND ID=5")
	 *
	 * @return boolean
	 */
	function filtersOnID() {
		$regexp = '/^(.*\.)?("|`)?ID("|`)?\s?=/';
		
		// Sometimes the ID filter will be the 2nd element, if there's a ClasssName filter first.
		if(isset($this->where[0]) && preg_match($regexp, $this->where[0])) return true;
		if(isset($this->where[1]) && preg_match($regexp, $this->where[1])) return true;
		
		return  false;
	}
	
	/**
	 * Checks whether this query is filtering on a foreign key, ie finding a has_many relationship
	 * 
	 * @todo Doesn't work with combined statements (e.g. "Foo='bar' AND ParentID=5")
	 *
	 * @return boolean
	 */
	function filtersOnFK() { 
		return (
			$this->where
			&& preg_match('/^(.*\.)?("|`)?[a-zA-Z]+ID("|`)?\s?=/', $this->where[0])
		);
	}
	
	/// VARIOUS TRANSFORMATIONS BELOW
	
	/**
	 * Return the number of rows in this query if the limit were removed.  Useful in paged data sets. 
	 * @return int 
	 */ 
	function unlimitedRowCount($column = null) {
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
				$countQuery = new SQLQuery();
				$countQuery->select("count(*)");
				$countQuery->from = array('(' . $clone->sql() . ') all_distinct');

				return $countQuery->execute()->value();

			} else {
				$clone->select = array("count(*)");
			}
		} else {
			$clone->select = array("count($column)");
		}

		$clone->groupby = null;
		return $clone->execute()->value();
	}

	/**
	 * Returns true if this query can be sorted by the given field.
	 */
	function canSortBy($fieldName) {
		$fieldName = preg_replace('/(\s+?)(A|DE)SC$/', '', $fieldName);
		
		return isset($this->select[$fieldName]);
	}


	/**
	 * Return the number of rows in this query if the limit were removed.  Useful in paged data sets.
	 * @return int
	 * 
	 * TODO Respect HAVING and GROUPBY, which can affect the result-count
	 */
	function count( $column = null) {
		// Choose a default column
		if($column == null) {
			if($this->groupby) {
				$column = 'DISTINCT ' . implode(", ", $this->groupby);
			} else {
				$column = '*';
			}
		}

		$clone = clone $this;
		$clone->select = array("count($column)");
		$clone->limit = null;
		$clone->orderby = null;
		$clone->groupby = null;
		
		$count = $clone->execute()->value();
		// If there's a limit set, then that limit is going to heavily affect the count
		if($this->limit) {
			if($count >= ($this->limit['start'] + $this->limit['limit']))
				return $this->limit['limit'];
			else
				return max(0, $count - $this->limit['start']);
			
		// Otherwise, the count is going to be the output of the SQL query
		} else {
			return $count;
		}
	}

    /**
     * Return a new SQLQuery that calls the given aggregate functions on this data.
     * @param $column An aggregate expression, such as 'MAX("Balance")', or a set of them.
     */
    function aggregate($column) {
		if($this->groupby || $this->limit) {
		    throw new Exception("SQLQuery::aggregate() doesn't work with groupby or limit, yet");
	    }

		$clone = clone $this;
		$clone->limit = null;
		$clone->orderby = null;
		$clone->groupby = null;
		$clone->select($column);

		return $clone;
    }
	
	/**
	 * Returns a query that returns only the first row of this query
	 */
	function firstRow() {
		$query = clone $this;
		$offset = $this->limit ? $this->limit['start'] : 0;
		$query->limit(1, $offset);
		return $query;
	}

	/**
	 * Returns a query that returns only the last row of this query
	 */
	function lastRow() {
		$query = clone $this;
		$offset = $this->limit ? $this->limit['start'] : 0;
		$query->limit(1, $this->count() + $offset - 1);
		return $query;
	}

}

