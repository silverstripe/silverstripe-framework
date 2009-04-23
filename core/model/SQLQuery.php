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
class SQLQuery extends Object {
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
	 * Construct a new SQLQuery.
	 * 
	 * @param array $select An array of fields to select.
	 * @param array $from An array of join clauses. The first one should be just the table name.
	 * @param array $where An array of filters, to be inserted into the WHERE clause.
	 * @param string $orderby An ORDER BY clause.
	 * @param array $groupby An array of fields to group by.
	 * @param array $having An array of having clauses.
	 * @param string $limit A LIMIT clause.
	 */
	function __construct($select = "*", $from = array(), $where = "", $orderby = "", $groupby = "", $having = "", $limit = "") {
		$this->select($select);
		// @todo 
		$this->from = is_array($from) ? $from : array(str_replace('`','',$from) => $from);
		$this->where($where);
		$this->orderby($orderby);
		$this->groupby($groupby);
		$this->having($having);
		$this->limit($limit);

		parent::__construct();
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
		$this->from[str_replace('`','',$table)] = $table;
		
		return $this;
	}
	
	/**
	 * Add a LEFT JOIN criteria to the FROM clause.
	 * 
	 * @return SQLQuery This instance 
	 */
	public function leftJoin($table, $onPredicate) {
		$this->from[$table] = "LEFT JOIN `$table` ON $onPredicate";
		return $this;
	}
	
	/**
	 * Add an INNER JOIN criteria to the FROM clause.
	 * 
	 * @return SQLQuery This instance 
	 */
	public function innerJoin($table, $onPredicate) {
		$this->from[$table] = "INNER JOIN $table ON $onPredicate";
		return $this;
	}
	
	/**
	 * Returns true if we are already joining to the given table alias
	 */
	public function isJoinedTo($tableAlias) {
		return isset($this->from[$tableAlias]);
	}
	
	/**
	 * Pass LIMIT clause either as SQL snippet or in array format.
	 *
	 * @param string|array $limit
	 * @return SQLQuery This instance
	 */
	public function limit($limit) {
		// Pass limit as array or SQL string value
		if(is_array($limit)) {
			if(!array_key_exists('limit',$limit)) user_error('SQLQuery::limit(): Wrong format for $limit', E_USER_ERROR);
			
			if(isset($limit['start']) && is_numeric($limit['start']) && isset($limit['limit']) && is_numeric($limit['limit'])) {
				// @todo MySQL specific LIMIT syntax
				$combinedLimit = (int)$limit['start'] . ',' . (int)$limit['limit'];
			} elseif(isset($limit['limit']) && is_numeric($limit['limit'])) {
				$combinedLimit = (int)$limit['limit'];
			} else {
				$combinedLimit = false;
			}
		} else {
			$combinedLimit = $limit;
		}
		
		if(!empty($combinedLimit)) $this->limit = $combinedLimit;
		
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
				$combinedOrderby = "`" . Convert::raw2sql($orderby['sort']) . "` " . Convert::raw2sql(strtoupper($orderby['dir']));
			} elseif(isset($orderby['sort']) && !empty($orderby['sort'])) {
				$combinedOrderby = "`" . Convert::raw2sql($orderby['sort']) . "`";
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
					$this->select[] = substr($sortPart,0,-5) . " AS _SortColumn{$i}";
					$newSorts[] = "_SortColumn{$i} DESC";
				} else if(substr(strtolower($sortPart),-4) == ' asc') {
					$this->select[] = substr($sortPart,0,-4) . " AS _SortColumn{$i}";
					$newSorts[] = "_SortColumn{$i} ASC";
				} else {
					$this->select[] = "$sortPart AS _SortColumn{$i}";
					$newSorts[] = "_SortColumn{$i} ASC";
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
	}
	
	/**
	 * Swap some text in the SQL query with another.
	 * @param string $old The old text.
	 * @param string $new The new text.
	 */
	function replaceText($old, $new) {
		if($this->select) foreach($this->select as $i => $item)
			$this->select[$i] = str_replace($old, $new, $item);

		if($this->from) foreach($this->from as $i => $item)
			$this->from[$i] = str_replace($old, $new, $item);

		if($this->where) {
			foreach($this->where as $i => $item)
				$this->where[$i] = str_replace($old, $new, $item);
		}
		
		$this->orderby = str_replace($old, $new, $this->orderby);

		if($this->groupby) {
			foreach($this->groupby as $i => $item)
				$this->groupby[$i] = str_replace($old, $new, $item);
		}

		if($this->having) {
			foreach($this->having as $i => $item)
				$this->having[$i] = str_replace($old, $new, $item);
		}
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
		if (!$this->from) return '';
		$distinct = $this->distinct ? "DISTINCT " : "";
		if($this->delete) {
			$text = "DELETE ";
		} else if($this->select) {
			$text = "SELECT $distinct" . implode(", ", $this->select);
		}
		$text .= " FROM " . implode(" ", $this->from);

		if($this->where) $text .= " WHERE (" . $this->getFilter(). ")";
		if($this->groupby) $text .= " GROUP BY " . implode(", ", $this->groupby);
		if($this->having) $text .= " HAVING ( " . implode(" ) AND ( ", $this->having) . " )";
		if($this->orderby) $text .= " ORDER BY " . $this->orderby;
		if($this->limit) $text .= " LIMIT " . $this->limit;
		
		return $text;
	}
	
	/**
	 * Return the generated SQL string for this query
	 * 
	 * @return string
	 */
	function __toString() {
		return $this->sql();
	}
	
	/**
	 * Execute this query.
	 * @return Query
	 */
	function execute() {
		return DB::query($this->sql());
	}
	
	/**
	 * Checks whether this query is for a specific ID in a table
	 * 
	 * @todo Doesn't work with combined statements (e.g. "Foo='bar' AND ID=5")
	 *
	 * @return boolean
	 */
	function filtersOnID() {
		return (
			$this->where 
			//&& count($this->where) == 1  
			&& preg_match('/^(.*\.)?("|`)?ID("|`)?\s?=/', $this->where[0])
		);
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
	 * 
	 * TODO Respect HAVING and GROUPBY, which can affect the result-count
	 */
	function unlimitedRowCount( $column = null) {
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
		return $clone->execute()->value();
	}
}

?>