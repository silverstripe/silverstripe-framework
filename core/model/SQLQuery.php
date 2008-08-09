<?php
/**
 * Object representing a SQL query.
 * The various parts of the SQL query can be manipulated individually.
 * @package sapphire
 * @subpackage model
 */
class SQLQuery extends Object {
	/**
	 * An array of fields to select.
	 * @var array
	 */
	public $select;
	
	/**
	 * An array of join clauses. The first one is just the table name.
	 * @var array
	 */
	public $from;
	
	/**
	 * An array of filters.
	 * @var array
	 */
	public $where;
	
	/**
	 * An ORDER BY clause.
	 * @var string
	 */
	public $orderby;
	
	/**
	 * An array of fields to group by.
	 * @var array
	 */
	public $groupby;
	
	/**
	 * An array of having clauses.
	 * @var array
	 */
	public $having;
	
	/**
	 * A limit clause.
	 * @var string
	 */
	public $limit;
	
	/**
	 * If this is true DISTINCT will be added to the SQL.
	 * @var boolean
	 */
	public $distinct;
	
	/**
	 * If this is true, this statement will delete rather than select.
	 * @var boolean
	 */
	public $delete;
	
	/**
	 * The logical connective used to join WHERE clauses. Defaults to AND.
	 * @var string
	 */
	private $connective = 'AND';
	
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
		if($select) $this->select = is_array($select) ? $select : array($select);
		if($from) $this->from = is_array($from) ? $from : array(str_replace('`','',$from) => $from);
		if($where) $this->where = is_array($where) ? $where : array($where);
		$this->orderby = $orderby;
		if($groupby) $this->groupby = is_array($groupby) ? $groupby : array($groupby);
		if($having) $this->having = is_array($having) ? $having : array($having);
		$this->limit = $limit;

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
	 * @return SQLQuery
	 */
	public function from($table) {
		$this->from[] = $table;
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
		$this->where[] = $filter;
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
	
	/// VARIOUS TRANSFORMATIONS BELOW
	
	/**
	 * Return the number of rows in this query if the limit were removed.  Useful in paged data sets.
	 * @return int
	 * 
	 * TODO Respect HAVING and GROUPBY, which can affect the result-count
	 */
	function unlimitedRowCount( $column = "*" ) {
		$clone = clone $this;
		$clone->select = array("count($column)");
		$clone->limit = null;
		$clone->orderby = null;
		$clone->groupby = null;
		return $clone->execute()->value();
	}
}

?>
