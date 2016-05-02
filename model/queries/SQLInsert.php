<?php

/**
 * Object representing a SQL INSERT query.
 * The various parts of the SQL query can be manipulated individually.
 *
 * @package framework
 * @subpackage model
 */
class SQLInsert extends SQLExpression implements SQLWriteExpression {

	/**
	 * List of rows to be inserted
	 *
	 * @var array[SQLAssignmentRow]
	 */
	protected $rows = array();

	/**
	 * The table name to insert into
	 *
	 * @var string
	 */
	protected $into = null;

	/**
	 * Construct a new SQLInsert object
	 *
	 * @param string $into Table name to insert into (ANSI quoted)
	 * @param array $assignments List of column assignments
	 * @return static
	 */
	public static function create($into = null, $assignments = array()) {
		return Injector::inst()->createWithArgs(__CLASS__, func_get_args());
	}

	/**
	 * Construct a new SQLInsert object
	 *
	 * @param string $into Table name to insert into (ANSI quoted)
	 * @param array $assignments List of column assignments
	 */
	function __construct($into = null, $assignments = array()) {
		$this->setInto($into);
		if(!empty($assignments)) {
			$this->setAssignments($assignments);
		}
	}

	/**
	 * Sets the table name to insert into
	 *
	 * @param string $into Single table name (ANSI quoted)
	 * @return self The self reference to this query
	 */
	public function setInto($into) {
		$this->into = $into;
		return $this;
	}

	/**
	 * Gets the table name to insert into
	 *
	 * @return string Single table name
	 */
	public function getInto() {
		return $this->into;
	}

	public function isEmpty() {
		return empty($this->into) || empty($this->rows);
	}

	/**
	 * Appends a new row to insert
	 *
	 * @param array|SQLAssignmentRow $data A list of data to include for this row
	 * @return self The self reference to this query
	 */
	public function addRow($data = null) {
		// Clear existing empty row
		if(($current = $this->currentRow()) && $current->isEmpty()) {
			array_pop($this->rows);
		}

		// Append data
		if($data instanceof SQLAssignmentRow) {
			$this->rows[] = $data;
		} else {
			$this->rows[] = new SQLAssignmentRow($data);
		}
		return $this;
	}

	/**
	 * Returns the current list of rows
	 *
	 * @return array[SQLAssignmentRow]
	 */
	public function getRows() {
		return $this->rows;
	}

	/**
	 * Returns the list of distinct column names used in this insert
	 *
	 * @return array
	 */
	public function getColumns() {
		$columns = array();
		foreach($this->getRows() as $row) {
			$columns = array_merge($columns, $row->getColumns());
		}
		return array_unique($columns);
	}

	/**
	 * Sets all rows to the given array
	 *
	 * @param array $rows the list of rows
	 * @return self The self reference to this query
	 */
	public function setRows(array $rows) {
		return $this->clear()->addRows($rows);
	}

	/**
	 * Adds the list of rows to the array
	 *
	 * @param array $rows the list of rows
	 * @return self The self reference to this query
	 */
	public function addRows(array $rows) {
		foreach($rows as $row) $this->addRow($row);
		return $this;
	}

	/**
	 * Returns the currently set row
	 *
	 * @param boolean $create Flag to indicate if a row should be created if none exists
	 * @return SQLAssignmentRow|false The row, or false if none exists
	 */
	public function currentRow($create = false) {
		$current = end($this->rows);
		if($create && !$current) {
			$this->rows[] = $current = new SQLAssignmentRow();
		}
		return $current;
	}

	public function addAssignments(array $assignments) {
		$this->currentRow(true)->addAssignments($assignments);
		return $this;
	}

	public function setAssignments(array $assignments) {
		$this->currentRow(true)->setAssignments($assignments);
		return $this;
	}

	public function getAssignments() {
		return $this->currentRow(true)->getAssignments();
	}

	public function assign($field, $value) {
		$this->currentRow(true)->assign($field, $value);
		return $this;
	}

	public function assignSQL($field, $sql) {
		$this->currentRow(true)->assignSQL($field, $sql);
		return $this;
	}

	/**
	 * Clears all currently set assigment values on the current row
	 *
	 * @return self The self reference to this query
	 */
	public function clearRow() {
		$this->currentRow(true)->clear();
		return $this;
	}

	/**
	 * Clears all rows
	 *
	 * @return self The self reference to this query
	 */
	public function clear() {
		$this->rows = array();
		return $this;
	}
}
