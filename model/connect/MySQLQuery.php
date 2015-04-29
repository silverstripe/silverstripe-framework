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
	 *
	 * @var mysqli_result
	 */
	protected $handle;

	/**
	 * Hook the result-set given into a Query class, suitable for use by SilverStripe.
	 * 
	 * @param mysqli_result $handle the internal mysql handle that is points to the resultset.
	 */
	public function __construct($handle = null) {
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
