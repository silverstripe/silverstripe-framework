<?php
/**
 * Object representing a SQL query.
 * The various parts of the SQL query can be manipulated individually.
 * 
 * Caution: Only supports SELECT (default) and DELETE at the moment.
 * 
 * @todo Add support for INSERT and UPDATE queries
 * 
 * @package sapphire
 * @subpackage model
 */
class SQLQuery {
	
	/**
	 * An array of fields to select.
	 * @var array
	 */
	public $select = array();
	
	/**
	 * An array of join clauses. The first one is just the table name.
	 * @var array
	 */
	public $from = array();
	
	/**
	 * An array of filters.
	 * @var array
	 */
	public $where = array();
	
	/**
	 * An ORDER BY clause.
	 * @var string
	 */
	public $orderby;
	
	/**
	 * An array of fields to group by.
	 * @var array
	 */
	public $groupby = array();
	
	/**
	 * An array of having clauses.
	 * @var array
	 */
	public $having = array();
	
	/**
	 * A limit clause.
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
	 * @var boolean
	 */
	public $delete = false;
	
	/**
	 * The logical connective used to join WHERE clauses. Defaults to AND.
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
			$this->select = func_get_args();
		} else {
			$this->select = is_array($fields) ? $fields : array($fields);
		}
		
		return $this;
	}
	
	/**
	 * Add addition columns to the select clause
	 */
    public function selectMore($fields) {
		if (func_num_args() > 1) $fields = func_get_args();
		if(is_array($fields)) {
		    foreach($fields as $field) $this->select[] = $field;
	    } else {
            $this->select[] = $fields;
	    }
    }
    
    /**
     * Return the SQL expression for the given field
     * @todo This should be refactored after $this->select is changed to make that easier
     */
    public function expressionForField($field) {
        foreach($this->select as $sel) {
		    if(preg_match('/AS +"?([^"]*)"?/i', $sel, $matches)) $selField = $matches[1];
		    else if(preg_match('/"([^"]*)"\."([^"]*)"/', $sel, $matches)) $selField = $matches[2];
		    else if(preg_match('/"?([^"]*)"?/', $sel, $matches)) $selField = $matches[2];
		    if($selField == $field) return $sel;
		}
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
	public function limit($limit) {
		if($limit && is_numeric($limit)) {
			$this->limit = array(
				'start' => 0,
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
	 * @todo Implement passing of multiple orderby pairs in nested array syntax,
	 * 	e.g. array(array('sort'=>'A','dir'=>'asc'),array('sort'=>'B'))
	 * 
	 * @param string|array $orderby
	 * @return SQLQuery This instance
	 */
	public function orderby($orderby) {
		// if passed as an array, assume two array values with column and direction (asc|desc) 
		if(is_array($orderby)) {
			if(!array_key_exists('sort', $orderby)) user_error('SQLQuery::orderby(): Wrong format for $orderby array', E_USER_ERROR);

			if(isset($orderby['sort']) && !empty($orderby['sort']) && isset($orderby['dir']) && !empty($orderby['dir'])) {
				$combinedOrderby = "\"" . Convert::raw2sql($orderby['sort']) . "\" " . Convert::raw2sql(strtoupper($orderby['dir']));
			} elseif(isset($orderby['sort']) && !empty($orderby['sort'])) {
				$combinedOrderby = "\"" . Convert::raw2sql($orderby['sort']) . "\"";
			} else {
				$combinedOrderby = false;
			}
		} else {
			$combinedOrderby = $orderby;
		}
		
		// If sort contains a function call, let's move the sort clause into a separate selected field.
		// Some versions of MySQL choke if you have a group function referenced directly in the ORDER BY
		if($combinedOrderby && strpos($combinedOrderby,'(') !== false) {
			// Sort can be "Col1 DESC|ASC, Col2 DESC|ASC", we need to handle that
			$sortParts = explode(",", $combinedOrderby);
				
			// If you have select if(X,A,B),C then the array will return 'if(X','A','B)','C'.
			// Turn this into 'if(X,A,B)','C' by counting brackets
			while(list($i,$sortPart) = each($sortParts)) {
				while(substr_count($sortPart,'(') > substr_count($sortPart,')')) {
					list($i,$nextSortPart) = each($sortParts);
					if($i === null) break;
					$sortPart .= ',' . $nextSortPart;
				}
				$lumpedSortParts[] = $sortPart;
			}
				
			foreach($lumpedSortParts as $i => $sortPart) {
				$sortPart = trim($sortPart);
				if(substr(strtolower($sortPart),-5) == ' desc') {
					$this->select[] = substr($sortPart,0,-5) . " AS \"_SortColumn{$i}\"";
					$newSorts[] = "\"_SortColumn{$i}\" DESC";
				} else if(substr(strtolower($sortPart),-4) == ' asc') {
					$this->select[] = substr($sortPart,0,-4) . " AS \"_SortColumn{$i}\"";
					$newSorts[] = "\"_SortColumn{$i}\" ASC";
				} else {
					$this->select[] = "$sortPart AS \"_SortColumn{$i}\"";
					$newSorts[] = "\"_SortColumn{$i}\" ASC";
				}
			}
				
			$combinedOrderby =  implode(", ", $newSorts);
		}
		
		if(!empty($combinedOrderby)) $this->orderby = $combinedOrderby;
		
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

	/**
	 * Return an SQL WHERE clause to filter a SELECT query.
	 *
	 * @return string
	 */
	function getFilter() {
		return ($this->where) ? implode(") {$this->connective} (" , $this->where) : '';
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
	function unlimitedRowCount( $column = null) {
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
				$countQuery->select = array("count(*)");
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
	 * Note that the implementation of this method is a little crude at the moment, it wil return
	 * "false" more often that is strictly necessary.
	 */
	function canSortBy($fieldName) {
		$fieldName = preg_replace('/(\s+?)(A|DE)SC$/', '', $fieldName);
		
		$sql = $this->sql();
	
		$selects = $this->select;
		foreach($selects as $i => $sel) {
			if (preg_match('/"(.*)"\."(.*)"/', $sel, $matches)) $selects[$i] = $matches[2];
		}
	
		$SQL_fieldName = Convert::raw2sql($fieldName);
		return (in_array($SQL_fieldName,$selects) || stripos($sql,"AS {$SQL_fieldName}"));
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
     * @param $columns An aggregate expression, such as 'MAX("Balance")', or an array of them.
     */
    function aggregate($columns) {
        if(!is_array($columns)) $columns = array($columns);

		if($this->groupby || $this->limit) {
		    throw new Exception("SQLQuery::aggregate() doesn't work with groupby or limit, yet");
	    }
        
        $clone = clone $this;
		$clone->limit = null;
		$clone->orderby = null;
		$clone->groupby = null;
		$clone->select = $columns;

        return $clone;
    }
	
	/**
	 * Returns a query that returns only the first row of this query
	 */
	function firstRow() {
		$query = clone $this;
		$offset = $this->limit ? $this->limit['start'] : 0;
		$query->limit(array('start' => $offset, 'limit' => 1));
		return $query;
	}

	/**
	 * Returns a query that returns only the last row of this query
	 */
	function lastRow() {
		$query = clone $this;
		$offset = $this->limit ? $this->limit['start'] : 0;
		$query->limit(array('start' => $this->count() + $offset - 1, 'limit' => 1));
		return $query;
	}

}

