<?php

/**
 * A result-set from a MySQL database (using MySQLiConnector)
 *
 * @package framework
 * @subpackage model
 */
class MySQLQuery extends SS_Query {

	/**
	 * The internal MySQL handle that points to the result set.
	 * Select queries will have mysqli_result as a value.
	 * Non-select queries will not
	 *
	 * @var mixed
	 */
	protected $handle;

	/**
	 * Hook the result-set given into a Query class, suitable for use by SilverStripe.
	 *
	 * @param MySQLiConnector $database The database object that created this query.
	 * @param mixed $handle the internal mysql handle that is points to the resultset.
	 * Non-mysqli_result values could be given for non-select queries (e.g. true)
	 */
	public function __construct($database, $handle) {
		$this->handle = $handle;
	}

	public function __destruct() {
		if (is_object($this->handle)) $this->handle->free();
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
