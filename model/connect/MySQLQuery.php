<?php

/**
 * A result-set from a MySQL database (using MySQLiConnector)
 * 
 * @package framework
 * @subpackage model
 */
class MySQLQuery extends SS_Query {
	
	/**
	 * The MySQLiConnector object that created this result set.
	 * 
	 * @var MySQLiConnector
	 */
	protected $database;

	/**
	 * The internal MySQL handle that points to the result set.
	 * 
	 * @var mysqli_result
	 */
	protected $handle;
	
	/**
	 * The related mysqli statement object if generated using a prepared query
	 * 
	 * @var mysqli_stmt
	 */
	protected $statement;

	/**
	 * Hook the result-set given into a Query class, suitable for use by SilverStripe.
	 * @param MySQLDatabase $database The database object that created this query.
	 * @param mysqli_result $handle the internal mysql handle that is points to the resultset.
	 * @param mysqli_stmt $statement The related statement, if present
	 */
	public function __construct(MySQLiConnector $database, $handle = null, $statement = null) {
		$this->database = $database;
		$this->handle = $handle;
		$this->statement = $statement;
	}

	public function __destruct() {
		if (is_object($this->handle)) $this->handle->free();
		// Don't close statement as these may be re-used across the life of this request
		// if (is_object($this->statement)) $this->statement->close();
	}

	public function seek($row) {
		if (is_object($this->handle)) return $this->handle->data_seek($row);
	}

	public function numRecords() {
		if (is_object($this->handle)) return $this->handle->num_rows;
	}

	public function nextRecord() {
		if (is_object($this->handle) && ($data = $this->handle->fetch_assoc())) {
			return $data;
		} else {
			return false;
		}
	}

}
